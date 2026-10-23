<?php

namespace App\Reporting;

use App\Models\User;
use App\Reporting\Definition\ReportDefinition;
use App\Reporting\Definition\SortSpec;
use App\Reporting\Processing\ConditionalFormatter;
use App\Reporting\Processing\FormulaCalculator;
use App\Reporting\Processing\PivotBuilder;
use App\Reporting\Processing\PostProcessor;
use App\Reporting\Processing\RowSorter;
use App\Reporting\Query\CompiledReport;
use App\Reporting\Query\QueryCompiler;
use App\Reporting\Query\RowFetcher;

/**
 * Runs a validated definition as a user:
 * SQL (scope, filters, grouping, SQL-able sorts) → formulas → PHP sorts →
 * post-processors → conditional formatting → preview cap.
 */
class ReportRunner
{
    public function __construct(private QueryCompiler $compiler) {}

    /**
     * @param  ?int  $maxRows  return at most this many rows (e.g. Limits::PREVIEW_ROWS); the definition's limit still applies
     */
    public function run(User $user, ReportDefinition $definition, ?int $maxRows = null): ReportResult
    {
        $compiled = $this->compiler->compile($definition, $user);
        $calculator = new FormulaCalculator($definition->calculated);

        if ($definition->mode === 'pivot') {
            return (new PivotBuilder($compiled, $calculator))->build($maxRows);
        }

        $sqlSorts = array_values(array_filter($definition->sort, fn (SortSpec $sort) => $compiled->select($sort->column) !== null));
        $sortInSql = count($sqlSorts) === count($definition->sort);

        $query = $compiled->orderBy($compiled->query(), $sortInSql ? $definition->sort : []);
        $rowCount = min($definition->limit, $compiled->count($query));

        // Anything computed across rows needs every row of the report, not just the preview.
        $needsAllRows = $definition->isGrouped() || ! $sortInSql || $definition->post !== []
            || array_filter($definition->formatting, fn ($rule) => in_array($rule->operator, ['top_n', 'bottom_n'], true)) !== [];
        $take = $needsAllRows ? $definition->limit : min($definition->limit, $maxRows ?? $definition->limit);

        $rows = $calculator->applyAll(RowFetcher::fetch($compiled, $query, $take));

        if (! $sortInSql) {
            $rows = RowSorter::sort($rows, $definition->sort);
        }

        $totals = $definition->grandTotals || $this->usesPercentOfTotal($definition)
            ? $this->totals($compiled, $calculator)
            : null;

        $rows = PostProcessor::apply($rows, $definition->post, $totals);
        $styles = ConditionalFormatter::styles($rows, $definition->formatting);

        if ($maxRows !== null && count($rows) > $maxRows) {
            $rows = array_slice($rows, 0, $maxRows);
            $styles = array_filter($styles, fn (int $index) => $index < $maxRows, ARRAY_FILTER_USE_KEY);
        }

        return new ReportResult(
            columns: $compiled->columns,
            rows: $this->visible($compiled, $rows),
            rowCount: $rowCount,
            truncated: count($rows) < $rowCount,
            totals: $definition->grandTotals && $totals !== null
                ? $this->visible($compiled, [PostProcessor::totals($totals, $definition->post)])[0]
                : null,
            subtotals: $definition->subtotals ? $this->visible($compiled, $this->subtotals($compiled, $calculator)) : [],
            styles: $styles,
        );
    }

    /**
     * Totals across all matching data, ignoring the row limit.
     *
     * @return array<string, mixed>
     */
    private function totals(CompiledReport $compiled, FormulaCalculator $calculator): array
    {
        if (! $compiled->definition->isGrouped()) {
            // Formulas over table rows don't total meaningfully; only sums of number columns do.
            return RowFetcher::fetch($compiled, $compiled->tableTotalsQuery())[0] ?? [];
        }

        if (! $compiled->hasMeasures()) {
            return []; // nothing is totalled
        }

        return $calculator->apply(RowFetcher::fetch($compiled, $compiled->query([]))[0] ?? []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function subtotals(CompiledReport $compiled, FormulaCalculator $calculator): array
    {
        $first = [$compiled->dimensionKeys()[0]];
        $query = $compiled->orderBy($compiled->query($first), [], $first);

        return $calculator->applyAll(RowFetcher::fetch($compiled, $query, Limits::MAX_GROUPS));
    }

    private function usesPercentOfTotal(ReportDefinition $definition): bool
    {
        foreach ($definition->post as $spec) {
            if ($spec->operation === 'percent_of_total') {
                return true;
            }
        }

        return false;
    }

    /**
     * Drop hidden formula inputs; keep visible columns in display order.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function visible(CompiledReport $compiled, array $rows): array
    {
        $keys = array_column($compiled->columns, 'key');

        return array_map(function (array $row) use ($keys) {
            $visible = [];

            foreach ($keys as $key) {
                $visible[$key] = $row[$key] ?? null;
            }

            return $visible;
        }, $rows);
    }
}

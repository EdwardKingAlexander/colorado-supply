<?php

namespace App\Reporting\Processing;

use App\Reporting\Definition\SortSpec;
use App\Reporting\FieldType;
use App\Reporting\Limits;
use App\Reporting\Query\CompiledReport;
use App\Reporting\Query\RowFetcher;
use App\Reporting\ReportResult;
use Illuminate\Database\Query\Builder;

/**
 * Builds a cross-tab from grouped queries. Every number comes from SQL at its
 * own grouping level (cells, row totals, column totals, grand total), so
 * averages and unique counts stay correct. More than MAX_PIVOT_COLUMNS
 * column values roll the rest into "Other" in SQL, not by adding up cells.
 */
final class PivotBuilder
{
    public const OTHER = '__other__';

    private const BLANK = "\0blank";

    public function __construct(
        private readonly CompiledReport $compiled,
        private readonly FormulaCalculator $calculator,
    ) {}

    public function build(?int $maxRows): ReportResult
    {
        $definition = $this->compiled->definition;
        $pivot = $definition->pivot;
        $column = $pivot->column;

        [$columnGroups, $overrides] = $this->columnGroups($column, $pivot->values[0]);

        $cells = $this->fetch($this->compiled->query([...$pivot->rows, $column], $overrides), Limits::MAX_PIVOT_CELLS + 1, [$column]);
        $truncated = count($cells) > Limits::MAX_PIVOT_CELLS;
        $cells = array_slice($cells, 0, Limits::MAX_PIVOT_CELLS);

        $rowGroups = $this->fetch($this->compiled->query($pivot->rows), Limits::MAX_GROUPS + 1);
        $truncated = $truncated || count($rowGroups) > Limits::MAX_GROUPS;
        $rowGroups = RowSorter::sort(
            $rowGroups,
            $definition->sort ?: array_map(fn (string $key) => new SortSpec($key), $pivot->rows),
        );

        $rowCount = min($definition->limit, count($rowGroups));
        $rowGroups = array_slice($rowGroups, 0, $maxRows === null ? $rowCount : min($rowCount, $maxRows));

        $grand = $definition->grandTotals ? ($this->fetch($this->compiled->query([]))[0] ?? []) : null;

        $columnValues = $this->orderedColumnValues($columnGroups, $column);
        $columns = $this->columnsMeta($columnValues);

        $cellIndex = [];
        foreach ($cells as $cell) {
            $cellIndex[$this->rowId($cell)][$this->columnId($cell[$column])] = $cell;
        }

        $rows = array_map(fn (array $group) => $this->pivotRow($group, $cellIndex[$this->rowId($group)] ?? [], $columnValues), $rowGroups);

        $totals = null;
        if ($grand !== null) {
            $byColumn = [];
            foreach ($columnGroups as $group) {
                $byColumn[$this->columnId($group[$column])] = $group;
            }

            $totals = $this->pivotRow(array_fill_keys($pivot->rows, null) + $grand, $byColumn, $columnValues);
        }

        $targets = [];
        foreach ($columns as $meta) {
            if (($meta['role'] ?? null) === 'pivot_cell') {
                $targets[$meta['source']][] = $meta['key'];
            }
        }

        return new ReportResult(
            columns: $columns,
            rows: $rows,
            rowCount: $rowCount,
            truncated: $truncated || count($rows) < $rowCount,
            totals: $totals,
            styles: ConditionalFormatter::styles($rows, $definition->formatting, $targets),
        );
    }

    /**
     * Totals per column value, plus the SQL override that rolls the tail into
     * "Other" when there are too many values.
     *
     * @return array{0: list<array<string, mixed>>, 1: array<string, array{0: string, 1: list<mixed>}>}
     */
    private function columnGroups(string $column, string $rankBy): array
    {
        $groups = $this->fetch($this->compiled->query([$column]), Limits::MAX_GROUPS, [$column]);

        if (count($groups) <= Limits::MAX_PIVOT_COLUMNS) {
            return [$groups, []];
        }

        $ranked = RowSorter::sort(
            array_values(array_filter($groups, fn (array $group) => $group[$column] !== null)),
            [new SortSpec($rankBy, 'desc')],
        );
        $keep = array_map(fn (array $group) => (string) $group[$column], array_slice($ranked, 0, Limits::MAX_PIVOT_COLUMNS - 1));

        $expression = $this->compiled->select($column)->expression;
        $text = $this->compiled->dialect->castText($expression);
        $placeholders = implode(', ', array_fill(0, count($keep), '?'));
        $overrides = [$column => [
            "CASE WHEN {$expression} IS NULL THEN NULL WHEN {$text} IN ({$placeholders}) THEN {$text} ELSE '".self::OTHER."' END",
            $keep,
        ]];

        return [$this->fetch($this->compiled->query([$column], $overrides), Limits::MAX_GROUPS, [$column]), $overrides];
    }

    /**
     * Column values in display order: values ascending, then blank, then Other.
     *
     * @param  list<array<string, mixed>>  $groups
     * @return list<array{id: string, raw: mixed, label: string}>
     */
    private function orderedColumnValues(array $groups, string $column): array
    {
        $type = $this->compiled->select($column)->type;
        $values = array_map(fn (array $group) => $group[$column], $groups);

        usort($values, function (mixed $a, mixed $b) use ($type) {
            $rank = fn (mixed $value) => match (true) {
                $value === self::OTHER => 2,
                $value === null => 1,
                default => 0,
            };

            return ($rank($a) <=> $rank($b))
                ?: RowSorter::compare(RowFetcher::cast($a, $type), RowFetcher::cast($b, $type));
        });

        return array_map(fn (mixed $value) => [
            'id' => $this->columnId($value),
            'raw' => $value === self::OTHER || $value === null ? $value : RowFetcher::cast($value, $type),
            'label' => $this->columnLabel($value, $column, $type),
        ], $values);
    }

    /**
     * @param  list<array{id: string, raw: mixed, label: string}>  $columnValues
     * @return list<array<string, mixed>>
     */
    private function columnsMeta(array $columnValues): array
    {
        $pivot = $this->compiled->definition->pivot;
        $byKey = array_column($this->compiled->columns, null, 'key');

        $columns = array_map(fn (string $key) => $byKey[$key], $pivot->rows);
        $columnSets = [...$columnValues, ...($this->compiled->definition->grandTotals ? [['id' => 'total', 'raw' => null, 'label' => 'Total']] : [])];

        foreach ($columnSets as $index => $columnValue) {
            foreach ($pivot->values as $value) {
                $isTotal = $columnValue['id'] === 'total';

                $columns[] = [
                    ...$byKey[$value],
                    'key' => $this->cellKey($value, $isTotal ? 'total' : (string) $index),
                    'label' => count($pivot->values) > 1 ? "{$columnValue['label']} · {$byKey[$value]['label']}" : $columnValue['label'],
                    'role' => $isTotal ? 'pivot_total' : 'pivot_cell',
                    'source' => $value,
                    'pivot' => [
                        'column_value' => $columnValue['raw'],
                        'column_label' => $columnValue['label'],
                        'value_label' => $byKey[$value]['label'],
                    ],
                ];
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $group  row dimension values (and, for totals, the grand totals)
     * @param  array<string, array<string, mixed>>  $cellsByColumn  column id => cell
     * @param  list<array{id: string, raw: mixed, label: string}>  $columnValues
     * @return array<string, mixed>
     */
    private function pivotRow(array $group, array $cellsByColumn, array $columnValues): array
    {
        $pivot = $this->compiled->definition->pivot;
        $row = [];

        foreach ($pivot->rows as $key) {
            $row[$key] = $group[$key] ?? null;
        }

        foreach ($columnValues as $index => $columnValue) {
            foreach ($pivot->values as $value) {
                $row[$this->cellKey($value, (string) $index)] = $cellsByColumn[$columnValue['id']][$value] ?? null;
            }
        }

        if ($this->compiled->definition->grandTotals) {
            foreach ($pivot->values as $value) {
                $row[$this->cellKey($value, 'total')] = $group[$value] ?? null;
            }
        }

        return $row;
    }

    private function cellKey(string $value, string $suffix): string
    {
        return "{$value}__{$suffix}";
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowId(array $row): string
    {
        return json_encode(array_map(fn (string $key) => $row[$key] ?? null, $this->compiled->definition->pivot->rows));
    }

    private function columnId(mixed $raw): string
    {
        return $raw === null ? self::BLANK : (string) $raw;
    }

    private function columnLabel(mixed $raw, string $column, FieldType $type): string
    {
        $options = array_column($this->compiled->columns, 'options', 'key')[$column] ?? [];

        return match (true) {
            $raw === self::OTHER => 'Other',
            $raw === null || $raw === '' => '(Blank)',
            $type === FieldType::Boolean => (int) $raw === 1 ? 'Yes' : 'No',
            isset($options[(string) $raw]) => $options[(string) $raw],
            default => (string) RowFetcher::cast($raw, $type),
        };
    }

    /**
     * @param  list<string>  $raw
     * @return list<array<string, mixed>>
     */
    private function fetch(Builder $query, ?int $limit = null, array $raw = []): array
    {
        return $this->calculator->applyAll(RowFetcher::fetch($this->compiled, $query, $limit, $raw));
    }
}

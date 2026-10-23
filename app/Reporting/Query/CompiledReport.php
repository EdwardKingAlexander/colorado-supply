<?php

namespace App\Reporting\Query;

use App\Reporting\Definition\ReportDefinition;
use App\Reporting\Definition\SortSpec;
use App\Reporting\Sql\SqlDialect;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * A definition compiled against its dataset: the scoped, joined and filtered
 * base query plus every expression it selects. Grouped reports can be
 * re-aggregated at any subset of their dimensions (totals, subtotals, pivots).
 */
final class CompiledReport
{
    /**
     * @param  list<Select>  $selects
     * @param  list<array<string, mixed>>  $columns  visible column metadata, in display order
     */
    public function __construct(
        private readonly Builder $base,
        public readonly array $selects,
        public readonly array $columns,
        public readonly ReportDefinition $definition,
        public readonly SqlDialect $dialect,
        private readonly string $rootAlias,
    ) {}

    public function select(string $key): ?Select
    {
        foreach ($this->selects as $select) {
            if ($select->key === $key) {
                return $select;
            }
        }

        return null;
    }

    public function hasMeasures(): bool
    {
        foreach ($this->selects as $select) {
            if ($select->measure) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function dimensionKeys(): array
    {
        return array_values(array_map(
            fn (Select $select) => $select->key,
            array_filter($this->selects, fn (Select $select) => ! $select->measure && ! $select->hidden),
        ));
    }

    /**
     * Table reports: every select, one row per record. Grouped reports: the
     * given dimensions (default: all of them) plus every measure.
     *
     * @param  ?list<string>  $groupBy
     * @param  array<string, array{0: string, 1: list<mixed>}>  $overrides  key => [expression, bindings]
     */
    public function query(?array $groupBy = null, array $overrides = []): Builder
    {
        $query = clone $this->base;

        if (! $this->definition->isGrouped()) {
            foreach ($this->selects as $select) {
                $query->selectRaw("{$select->expression} as {$select->alias}");
            }

            return $query;
        }

        $groupBy ??= $this->dimensionKeys();

        foreach ($this->selects as $select) {
            if (! $select->measure && ! in_array($select->key, $groupBy, true)) {
                continue;
            }

            [$expression, $bindings] = $overrides[$select->key] ?? [$select->expression, []];
            $query->selectRaw("{$expression} as {$select->alias}", $bindings);

            // By alias, so an expression with bindings (the pivot "Other"
            // CASE) isn't repeated where MySQL's ONLY_FULL_GROUP_BY could
            // fail to match it. Aliases (c0, h1) never shadow real columns.
            if (! $select->measure) {
                $query->groupBy($select->alias);
            }
        }

        return $query;
    }

    /**
     * Table reports' totals row: the sum of every visible number column.
     */
    public function tableTotalsQuery(): Builder
    {
        $query = clone $this->base;
        $any = false;

        foreach ($this->selects as $select) {
            if (! $select->hidden && $select->type->isNumeric()) {
                $query->selectRaw("SUM({$select->expression}) as {$select->alias}");
                $any = true;
            }
        }

        return $any ? $query : $query->selectRaw('NULL as no_totals');
    }

    /**
     * Apply SQL sorts on selected keys plus a stable tie-break.
     *
     * @param  list<SortSpec>  $sorts
     * @param  ?list<string>  $groupBy  the dimensions the query was grouped by
     */
    public function orderBy(Builder $query, array $sorts, ?array $groupBy = null): Builder
    {
        foreach ($sorts as $sort) {
            $select = $this->select($sort->column);

            if ($select !== null) {
                $query->orderBy($select->alias, $sort->direction);
            }
        }

        if (! $this->definition->isGrouped()) {
            return $query->orderBy("{$this->rootAlias}.id");
        }

        foreach ($groupBy ?? $this->dimensionKeys() as $key) {
            $query->orderBy($this->select($key)->alias);
        }

        return $query;
    }

    /**
     * How many rows the query returns (grouped queries count their groups).
     */
    public function count(Builder $query): int
    {
        return DB::query()->fromSub((clone $query)->reorder(), 'report_rows')->count();
    }
}

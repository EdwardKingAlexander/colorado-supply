<?php

namespace App\Reporting;

final class ReportResult
{
    /**
     * @param  list<array<string, mixed>>  $columns  key, label, type, format, role, ... (pivot cells add `pivot` and `source`)
     * @param  list<array<string, mixed>>  $rows  keyed by column key
     * @param  int  $rowCount  rows (or pivot row groups) the report would return, within its own limit
     * @param  bool  $truncated  fewer rows were returned than $rowCount, or a group cap was hit
     * @param  ?array<string, mixed>  $totals  grand totals across all matching data
     * @param  list<array<string, mixed>>  $subtotals  one row per value of the first grouping column
     * @param  array<int, array<string, list<string>>>  $styles  row index => column key => semantic styles
     */
    public function __construct(
        public readonly array $columns,
        public readonly array $rows,
        public readonly int $rowCount,
        public readonly bool $truncated,
        public readonly ?array $totals = null,
        public readonly array $subtotals = [],
        public readonly array $styles = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'columns' => $this->columns,
            'rows' => $this->rows,
            'row_count' => $this->rowCount,
            'truncated' => $this->truncated,
            'totals' => $this->totals,
            'subtotals' => $this->subtotals,
            // An object even when empty or sequential, so clients can always index by row.
            'styles' => (object) $this->styles,
        ];
    }
}

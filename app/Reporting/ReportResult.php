<?php

namespace App\Reporting;

final class ReportResult
{
    /**
     * @param  list<array{key: string, label: string, field: string, type: string, format: ?string, bucket: ?string, options: array<string, string>}>  $columns
     * @param  list<array<string, mixed>>  $rows  keyed by column key
     * @param  int  $rowCount  rows the report would return, within its own limit
     * @param  bool  $truncated  fewer rows were returned than $rowCount
     */
    public function __construct(
        public readonly array $columns,
        public readonly array $rows,
        public readonly int $rowCount,
        public readonly bool $truncated,
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
        ];
    }
}

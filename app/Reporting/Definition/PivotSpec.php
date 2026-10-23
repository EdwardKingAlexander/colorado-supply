<?php

namespace App\Reporting\Definition;

/**
 * Cross-tab layout over column keys: row dimensions down the side, one column
 * dimension across the top, and the measures (aggregated or calculated
 * columns) in the cells.
 */
final class PivotSpec
{
    /**
     * @param  list<string>  $rows
     * @param  list<string>  $values
     */
    public function __construct(
        public readonly array $rows,
        public readonly string $column,
        public readonly array $values,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(array_values($data['rows'] ?? []), $data['column'], array_values($data['values']));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['rows' => $this->rows, 'column' => $this->column, 'values' => $this->values];
    }
}

<?php

namespace App\Reporting\Definition;

final class SortSpec
{
    /**
     * @param  string  $column  a column key from the definition
     * @param  'asc'|'desc'  $direction
     */
    public function __construct(
        public readonly string $column,
        public readonly string $direction = 'asc',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['column'], $data['dir'] ?? 'asc');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return ['column' => $this->column, 'dir' => $this->direction];
    }
}

<?php

namespace App\Reporting\Definition;

use App\Reporting\Limits;

/**
 * A report as stored and sent by the builder. Build it through
 * ReportDefinitionValidator::validated(); fromArray() trusts its input.
 */
final class ReportDefinition
{
    public const VERSION = 1;

    /** Modes the engine can run. Phase 2 adds summary and pivot. */
    public const MODES = ['table'];

    /**
     * @param  list<ColumnSpec>  $columns
     * @param  list<SortSpec>  $sort
     */
    public function __construct(
        public readonly string $dataset,
        public readonly array $columns,
        public readonly string $mode = 'table',
        public readonly ?FilterGroup $filters = null,
        public readonly array $sort = [],
        public readonly int $limit = Limits::DEFAULT_ROW_LIMIT,
        public readonly int $version = self::VERSION,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $columns = array_values($data['columns'] ?? []);
        $keys = ColumnSpec::keysFor($columns);

        return new self(
            dataset: $data['dataset'],
            columns: array_map(fn (array $column, string $key) => ColumnSpec::fromArray($column, $key), $columns, $keys),
            mode: $data['mode'] ?? 'table',
            filters: empty($data['filters']['rules']) ? null : FilterGroup::fromArray($data['filters']),
            sort: array_map(fn (array $sort) => SortSpec::fromArray($sort), array_values($data['sort'] ?? [])),
            limit: (int) ($data['limit'] ?? Limits::DEFAULT_ROW_LIMIT),
            version: (int) ($data['version'] ?? self::VERSION),
        );
    }

    public function column(string $key): ?ColumnSpec
    {
        foreach ($this->columns as $column) {
            if ($column->key === $key) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'dataset' => $this->dataset,
            'mode' => $this->mode,
            'columns' => array_map(fn (ColumnSpec $column) => $column->toArray(), $this->columns),
            'filters' => $this->filters?->toArray(),
            'sort' => array_map(fn (SortSpec $sort) => $sort->toArray(), $this->sort),
            'limit' => $this->limit,
        ];
    }
}

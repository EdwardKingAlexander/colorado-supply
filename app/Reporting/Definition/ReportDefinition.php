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

    /** table: raw rows. summary: grouped by every non-aggregated column. pivot: cross-tab. */
    public const MODES = ['table', 'summary', 'pivot'];

    /**
     * @param  list<ColumnSpec>  $columns
     * @param  list<SortSpec>  $sort
     * @param  list<CalculatedField>  $calculated
     * @param  list<PostProcessorSpec>  $post
     * @param  list<FormatRule>  $formatting
     */
    public function __construct(
        public readonly string $dataset,
        public readonly array $columns,
        public readonly string $mode = 'table',
        public readonly ?FilterGroup $filters = null,
        public readonly array $sort = [],
        public readonly int $limit = Limits::DEFAULT_ROW_LIMIT,
        public readonly int $version = self::VERSION,
        public readonly array $calculated = [],
        public readonly array $post = [],
        public readonly bool $grandTotals = false,
        public readonly bool $subtotals = false,
        public readonly array $formatting = [],
        public readonly ?PivotSpec $pivot = null,
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
            calculated: array_map(fn (array $field) => CalculatedField::fromArray($field), array_values($data['calculated'] ?? [])),
            post: array_map(fn (array $post) => PostProcessorSpec::fromArray($post), array_values($data['post'] ?? [])),
            grandTotals: (bool) ($data['totals']['grand'] ?? false),
            subtotals: (bool) ($data['totals']['subtotals'] ?? false),
            formatting: array_map(fn (array $rule) => FormatRule::fromArray($rule), array_values($data['formatting'] ?? [])),
            pivot: isset($data['pivot']) ? PivotSpec::fromArray($data['pivot']) : null,
        );
    }

    public function isGrouped(): bool
    {
        return $this->mode !== 'table';
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
     * @return list<ColumnSpec>
     */
    public function dimensions(): array
    {
        return array_values(array_filter($this->columns, fn (ColumnSpec $column) => $column->isDimension()));
    }

    public function calculatedField(string $key): ?CalculatedField
    {
        foreach ($this->calculated as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'version' => $this->version,
            'dataset' => $this->dataset,
            'mode' => $this->mode,
            'columns' => array_map(fn (ColumnSpec $column) => $column->toArray(), $this->columns),
            'calculated' => array_map(fn (CalculatedField $field) => $field->toArray(), $this->calculated),
            'post' => array_map(fn (PostProcessorSpec $post) => $post->toArray(), $this->post),
            'filters' => $this->filters?->toArray(),
            'sort' => array_map(fn (SortSpec $sort) => $sort->toArray(), $this->sort),
            'limit' => $this->limit,
            'totals' => $this->grandTotals || $this->subtotals
                ? ['grand' => $this->grandTotals, 'subtotals' => $this->subtotals]
                : null,
            'formatting' => array_map(fn (FormatRule $rule) => $rule->toArray(), $this->formatting),
            'pivot' => $this->pivot?->toArray(),
        ], fn ($value, string $key) => in_array($key, ['filters', 'sort'], true) || ($value !== null && $value !== []), ARRAY_FILTER_USE_BOTH);
    }
}

<?php

namespace App\Reporting\Definition;

use App\Reporting\DateBucket;

final class ColumnSpec
{
    public const FORMATS = ['text', 'number', 'currency', 'percent', 'date', 'datetime'];

    public const KEY_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';

    public function __construct(
        public readonly string $key,
        public readonly string $field,
        public readonly ?string $label = null,
        public readonly ?DateBucket $bucket = null,
        public readonly ?string $format = null,
    ) {}

    /**
     * Keys for raw column arrays: the explicit `key` when given, otherwise one
     * derived from field and bucket (order.created_at + month →
     * order_created_at_month), de-duplicated with a numeric suffix.
     *
     * @param  array<int, mixed>  $columns
     * @return list<string>
     */
    public static function keysFor(array $columns): array
    {
        $keys = [];
        $seen = [];

        foreach (array_values($columns) as $index => $column) {
            $column = is_array($column) ? $column : [];
            $explicit = $column['key'] ?? null;

            if (is_string($explicit) && $explicit !== '') {
                $keys[] = $explicit;
                $seen[$explicit] = true;

                continue;
            }

            $field = is_string($column['field'] ?? null) ? $column['field'] : "column_{$index}";
            $bucket = is_string($column['bucket'] ?? null) ? '_'.$column['bucket'] : '';
            $base = substr(strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $field.$bucket)), 0, 60);
            $base = preg_match('/^[a-z]/', $base) ? $base : 'c_'.$base;

            $key = $base;
            for ($suffix = 2; isset($seen[$key]); $suffix++) {
                $key = "{$base}_{$suffix}";
            }

            $keys[] = $key;
            $seen[$key] = true;
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $key): self
    {
        return new self(
            key: $key,
            field: $data['field'],
            label: $data['label'] ?? null,
            bucket: isset($data['bucket']) ? DateBucket::from($data['bucket']) : null,
            format: $data['format'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'field' => $this->field,
            'label' => $this->label,
            'bucket' => $this->bucket?->value,
            'format' => $this->format,
        ], fn ($value) => $value !== null);
    }
}

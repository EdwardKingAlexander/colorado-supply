<?php

namespace App\Reporting\Definition;

/**
 * Adds a derived column next to an existing numeric column.
 */
final class PostProcessorSpec
{
    public const OPERATIONS = [
        'percent_of_total' => '% of total',
        'running_total' => 'Running total',
        'change_vs_previous' => 'Change',
        'percent_change_vs_previous' => '% change',
        'rank' => 'Rank',
    ];

    public function __construct(
        public readonly string $key,
        public readonly string $column,
        public readonly string $operation,
        public readonly ?string $label = null,
    ) {}

    public static function defaultKey(string $column, string $operation): string
    {
        return substr("{$column}_{$operation}", 0, 64);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['key'] ?? self::defaultKey($data['column'], $data['op']),
            $data['column'],
            $data['op'],
            $data['label'] ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'column' => $this->column,
            'op' => $this->operation,
            'label' => $this->label,
        ], fn ($value) => $value !== null);
    }
}

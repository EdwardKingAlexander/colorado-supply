<?php

namespace App\Reporting\Definition;

/**
 * A conditional formatting rule. Styles are semantic so the UI, XLSX and PDF
 * can each render them their own way.
 */
final class FormatRule
{
    public const OPERATORS = ['gt', 'gte', 'lt', 'lte', 'eq', 'neq', 'between', 'negative', 'is_null', 'top_n', 'bottom_n'];

    /** Operators that compare numbers (the rest work on any value). */
    public const NUMERIC_OPERATORS = ['gt', 'gte', 'lt', 'lte', 'between', 'negative', 'top_n', 'bottom_n'];

    public const STYLES = ['positive', 'negative', 'warning', 'muted', 'bold'];

    public function __construct(
        public readonly string $column,
        public readonly string $operator,
        public readonly mixed $value,
        public readonly string $style,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['column'], $data['when']['op'], $data['when']['value'] ?? null, $data['style']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $when = ['op' => $this->operator];

        if ($this->value !== null) {
            $when['value'] = $this->value;
        }

        return ['column' => $this->column, 'when' => $when, 'style' => $this->style];
    }
}

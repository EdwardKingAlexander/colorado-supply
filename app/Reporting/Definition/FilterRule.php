<?php

namespace App\Reporting\Definition;

use App\Reporting\Operator;

final class FilterRule
{
    public function __construct(
        public readonly string $field,
        public readonly Operator $operator,
        public readonly mixed $value = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['field'], Operator::from($data['op']), $data['value'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $rule = ['field' => $this->field, 'op' => $this->operator->value];

        if ($this->operator->valueShape() !== 'none') {
            $rule['value'] = $this->value;
        }

        return $rule;
    }
}

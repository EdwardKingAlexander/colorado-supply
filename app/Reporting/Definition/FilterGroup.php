<?php

namespace App\Reporting\Definition;

final class FilterGroup
{
    /**
     * @param  'and'|'or'  $logic
     * @param  list<FilterRule|FilterGroup>  $rules
     */
    public function __construct(
        public readonly string $logic,
        public readonly array $rules,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['logic'] ?? 'and',
            array_map(
                fn (array $rule) => isset($rule['rules']) ? self::fromArray($rule) : FilterRule::fromArray($rule),
                array_values($data['rules'] ?? []),
            ),
        );
    }

    /**
     * @return list<FilterRule>
     */
    public function flatRules(): array
    {
        $rules = [];

        foreach ($this->rules as $rule) {
            array_push($rules, ...($rule instanceof self ? $rule->flatRules() : [$rule]));
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'logic' => $this->logic,
            'rules' => array_map(fn (FilterRule|FilterGroup $rule) => $rule->toArray(), $this->rules),
        ];
    }
}

<?php

namespace App\Reporting\Formula;

final class Token
{
    public const NUMBER = 'number';

    public const IDENTIFIER = 'identifier';

    public const OPERATOR = 'operator';

    public const END = 'end';

    public function __construct(
        public readonly string $type,
        public readonly string $value,
        public readonly int $position,
    ) {}

    public function is(string $type, ?string $value = null): bool
    {
        return $this->type === $type && ($value === null || $this->value === $value);
    }
}

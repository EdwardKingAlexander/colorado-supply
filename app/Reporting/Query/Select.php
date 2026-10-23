<?php

namespace App\Reporting\Query;

use App\Reporting\FieldType;

/**
 * One selected expression: a report column, or a hidden value a formula needs.
 */
final class Select
{
    public function __construct(
        public readonly string $key,
        public readonly string $alias,
        public readonly string $expression,
        public readonly FieldType $type,
        public readonly bool $measure,
        public readonly bool $hidden = false,
    ) {}
}

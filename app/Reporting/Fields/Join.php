<?php

namespace App\Reporting\Fields;

final class Join
{
    /**
     * @param  list<string>  $dependsOn  join keys that must be added first
     */
    public function __construct(
        public readonly string $table,
        public readonly string $alias,
        public readonly string $first,
        public readonly string $second,
        public readonly bool $inner = false,
        public readonly array $dependsOn = [],
    ) {}
}

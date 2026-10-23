<?php

namespace App\Reporting\Formula;

/**
 * A bare name: a column or calculated key (no dot), or a raw field value
 * (dotted, table reports only).
 */
final class ReferenceNode extends Node
{
    public function __construct(public readonly string $name, int $position)
    {
        parent::__construct($position);
    }

    public function isField(): bool
    {
        return str_contains($this->name, '.');
    }
}

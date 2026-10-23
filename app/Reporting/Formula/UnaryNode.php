<?php

namespace App\Reporting\Formula;

final class UnaryNode extends Node
{
    public function __construct(public readonly Node $operand, int $position)
    {
        parent::__construct($position);
    }

    public function children(): array
    {
        return [$this->operand];
    }
}

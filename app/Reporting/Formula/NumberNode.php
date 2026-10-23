<?php

namespace App\Reporting\Formula;

final class NumberNode extends Node
{
    public function __construct(public readonly float $value, int $position)
    {
        parent::__construct($position);
    }
}

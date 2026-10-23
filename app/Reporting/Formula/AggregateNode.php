<?php

namespace App\Reporting\Formula;

use App\Reporting\Aggregate;

/**
 * SUM(line.line_total) and friends: computed in SQL, read from the row.
 */
final class AggregateNode extends Node
{
    public function __construct(public readonly Aggregate $aggregate, public readonly string $field, int $position)
    {
        parent::__construct($position);
    }

    /** The hidden result key the compiler selects this aggregate as. */
    public function key(): string
    {
        return '__'.$this->aggregate->value.'__'.str_replace('.', '__', $this->field);
    }
}

<?php

namespace App\Reporting\Formula;

/**
 * A formula syntax tree node. One small class per kind keeps the evaluator a
 * plain match on type.
 */
abstract class Node
{
    public function __construct(public readonly int $position) {}

    /**
     * @return list<Node>
     */
    public function children(): array
    {
        return [];
    }
}

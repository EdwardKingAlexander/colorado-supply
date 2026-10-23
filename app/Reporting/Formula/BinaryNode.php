<?php

namespace App\Reporting\Formula;

final class BinaryNode extends Node
{
    public function __construct(
        public readonly string $operator,
        public readonly Node $left,
        public readonly Node $right,
        int $position,
    ) {
        parent::__construct($position);
    }

    public function children(): array
    {
        return [$this->left, $this->right];
    }
}

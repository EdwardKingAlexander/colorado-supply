<?php

namespace App\Reporting\Formula;

final class CallNode extends Node
{
    /**
     * @param  list<Node>  $arguments
     */
    public function __construct(public readonly string $function, public readonly array $arguments, int $position)
    {
        parent::__construct($position);
    }

    public function children(): array
    {
        return $this->arguments;
    }
}

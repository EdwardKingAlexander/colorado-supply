<?php

namespace App\Reporting\Formula;

use InvalidArgumentException;

class FormulaException extends InvalidArgumentException
{
    public function __construct(string $message, public readonly int $position = 0)
    {
        parent::__construct($message);
    }

    /**
     * The message with a 1-based character position, for showing to users.
     */
    public function describe(): string
    {
        return "{$this->getMessage()} (at character ".($this->position + 1).')';
    }
}

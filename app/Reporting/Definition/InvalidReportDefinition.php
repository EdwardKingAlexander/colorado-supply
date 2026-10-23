<?php

namespace App\Reporting\Definition;

use InvalidArgumentException;

class InvalidReportDefinition extends InvalidArgumentException
{
    /**
     * @param  array<string, string>  $errors  JSON path => message
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The report definition is invalid: '.implode(' ', $errors));
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}

<?php

namespace App\Contracts;

use App\Models\ContractDocument;
use App\Services\DocumentParsing\ParseResult;

interface DocumentParserInterface
{
    /**
     * Check if this parser can handle the given document.
     */
    public function canParse(ContractDocument $document): bool;

    /**
     * Parse the document and extract content.
     */
    public function parse(ContractDocument $document): ParseResult;

    /**
     * Get the unique driver name for this parser.
     */
    public function getDriverName(): string;

    /**
     * Get the priority of this parser (higher = tried first).
     */
    public function getPriority(): int;
}

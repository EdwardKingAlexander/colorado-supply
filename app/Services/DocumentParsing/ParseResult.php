<?php

namespace App\Services\DocumentParsing;

class ParseResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $extractedText = null,
        public readonly ?array $structuredData = null,
        public readonly ?array $pageMap = null,
        public readonly ?array $metrics = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $driverUsed = null,
    ) {}

    /**
     * Create a successful parse result.
     */
    public static function success(
        string $extractedText,
        ?array $structuredData = null,
        ?array $pageMap = null,
        ?array $metrics = null,
        ?string $driverUsed = null,
    ): self {
        return new self(
            success: true,
            extractedText: $extractedText,
            structuredData: $structuredData,
            pageMap: $pageMap,
            metrics: $metrics,
            driverUsed: $driverUsed,
        );
    }

    /**
     * Create a failed parse result.
     */
    public static function failure(string $errorMessage, ?string $driverUsed = null): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            driverUsed: $driverUsed,
        );
    }

    /**
     * Get word count from extracted text.
     */
    public function getWordCount(): int
    {
        if (! $this->extractedText) {
            return 0;
        }

        return str_word_count($this->extractedText);
    }

    /**
     * Get page count from metrics or page map.
     */
    public function getPageCount(): int
    {
        if ($this->metrics && isset($this->metrics['pages'])) {
            return (int) $this->metrics['pages'];
        }

        if ($this->pageMap) {
            return count($this->pageMap);
        }

        return 0;
    }
}

<?php

namespace App\Services\DocumentParsing\Parsers;

use App\Contracts\DocumentParserInterface;
use App\Models\ContractDocument;
use App\Services\DocumentParsing\ParseResult;
use Smalot\PdfParser\Parser;

class NativePdfParser implements DocumentParserInterface
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser;
    }

    public function canParse(ContractDocument $document): bool
    {
        return str_contains($document->mime_type, 'pdf');
    }

    public function parse(ContractDocument $document): ParseResult
    {
        try {
            $filePath = $document->full_path;

            if (! file_exists($filePath)) {
                return ParseResult::failure(
                    "File not found: {$filePath}",
                    $this->getDriverName()
                );
            }

            $pdf = $this->parser->parseFile($filePath);

            // Extract text from all pages
            $pages = $pdf->getPages();
            $pageTexts = [];
            $fullText = '';

            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();
                $pageTexts[$pageNumber + 1] = $pageText;
                $fullText .= $pageText . "\n\n";
            }

            $fullText = trim($fullText);

            // Check if we got meaningful text
            if (strlen($fullText) < 50) {
                return ParseResult::failure(
                    'PDF appears to be scanned or image-based. OCR required.',
                    $this->getDriverName()
                );
            }

            // Get document metadata
            $details = $pdf->getDetails();

            $metrics = [
                'pages' => count($pages),
                'words' => str_word_count($fullText),
                'characters' => strlen($fullText),
                'title' => $details['Title'] ?? null,
                'author' => $details['Author'] ?? null,
                'creator' => $details['Creator'] ?? null,
                'creation_date' => $details['CreationDate'] ?? null,
            ];

            // Build page map
            $pageMap = [];
            foreach ($pageTexts as $pageNum => $text) {
                $pageMap[] = [
                    'page' => $pageNum,
                    'text' => $text,
                    'word_count' => str_word_count($text),
                    'char_count' => strlen($text),
                ];
            }

            // Basic structure extraction (headings, sections)
            $structuredData = $this->extractStructure($fullText);

            return ParseResult::success(
                extractedText: $fullText,
                structuredData: $structuredData,
                pageMap: $pageMap,
                metrics: $metrics,
                driverUsed: $this->getDriverName(),
            );
        } catch (\Exception $e) {
            return ParseResult::failure(
                "PDF parsing failed: {$e->getMessage()}",
                $this->getDriverName()
            );
        }
    }

    public function getDriverName(): string
    {
        return 'native_pdf';
    }

    public function getPriority(): int
    {
        return 100; // High priority - try this first for PDFs
    }

    /**
     * Extract basic structure from text (headings, sections).
     */
    protected function extractStructure(string $text): array
    {
        $structure = [
            'sections' => [],
            'headings' => [],
        ];

        // Split into lines
        $lines = explode("\n", $text);

        $currentSection = null;
        $sectionContent = '';

        foreach ($lines as $lineNumber => $line) {
            $trimmedLine = trim($line);

            if (empty($trimmedLine)) {
                continue;
            }

            // Detect potential section headers
            // Common patterns: ALL CAPS, numbered sections, short lines followed by content
            $isHeading = $this->detectHeading($trimmedLine);

            if ($isHeading) {
                // Save previous section
                if ($currentSection !== null) {
                    $structure['sections'][] = [
                        'title' => $currentSection,
                        'content' => trim($sectionContent),
                        'line_start' => $lineNumber - substr_count($sectionContent, "\n"),
                    ];
                }

                $currentSection = $trimmedLine;
                $sectionContent = '';

                $structure['headings'][] = [
                    'text' => $trimmedLine,
                    'line' => $lineNumber + 1,
                    'level' => $this->guessHeadingLevel($trimmedLine),
                ];
            } else {
                $sectionContent .= $trimmedLine . "\n";
            }
        }

        // Save last section
        if ($currentSection !== null && ! empty(trim($sectionContent))) {
            $structure['sections'][] = [
                'title' => $currentSection,
                'content' => trim($sectionContent),
            ];
        }

        return $structure;
    }

    /**
     * Detect if a line is likely a heading.
     */
    protected function detectHeading(string $line): bool
    {
        // Too long to be a heading
        if (strlen($line) > 100) {
            return false;
        }

        // Section number patterns (e.g., "1.0", "A.", "Section 1", "PART I")
        $sectionPatterns = [
            '/^(?:SECTION|PART|ARTICLE|CLAUSE)\s+[A-Z0-9]+/i',
            '/^[A-Z]\.\s+[A-Z]/',
            '/^\d+\.\d*\s+[A-Z]/',
            '/^[IVXLCDM]+\.\s+/',
        ];

        foreach ($sectionPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        // ALL CAPS lines that are short (likely headings)
        if (preg_match('/^[A-Z\s\-\d\.]+$/', $line) && strlen($line) > 3 && strlen($line) < 80) {
            // Count uppercase vs total letters
            $letters = preg_replace('/[^a-zA-Z]/', '', $line);
            $uppercase = preg_replace('/[^A-Z]/', '', $line);

            if (strlen($letters) > 0 && strlen($uppercase) / strlen($letters) > 0.8) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guess heading level based on formatting.
     */
    protected function guessHeadingLevel(string $heading): int
    {
        // PART/SECTION = level 1
        if (preg_match('/^(?:PART|SECTION)\s+/i', $heading)) {
            return 1;
        }

        // ARTICLE = level 2
        if (preg_match('/^ARTICLE\s+/i', $heading)) {
            return 2;
        }

        // Numbered sections
        if (preg_match('/^(\d+)\.(\d*)/', $heading, $matches)) {
            $subLevel = ! empty($matches[2]) ? 1 : 0;

            return 2 + $subLevel;
        }

        // Letter sections
        if (preg_match('/^[A-Z]\.\s+/', $heading)) {
            return 3;
        }

        return 4; // Default
    }
}

<?php

namespace App\Services\DocumentParsing\Parsers;

use App\Contracts\DocumentParserInterface;
use App\Models\ContractDocument;
use App\Services\DocumentParsing\ParseResult;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

class DocxParser implements DocumentParserInterface
{
    public function canParse(ContractDocument $document): bool
    {
        $docxMimeTypes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
        ];

        return in_array($document->mime_type, $docxMimeTypes);
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

            // Determine reader type based on extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $readerType = $extension === 'doc' ? 'MsDoc' : 'Word2007';

            $phpWord = IOFactory::load($filePath, $readerType);

            $fullText = '';
            $sections = [];
            $tables = [];
            $headings = [];
            $currentHeading = null;
            $sectionContent = '';

            foreach ($phpWord->getSections() as $sectionIndex => $section) {
                $sectionText = $this->extractSectionText($section, $headings, $tables);
                $fullText .= $sectionText . "\n\n";

                $sections[] = [
                    'index' => $sectionIndex + 1,
                    'text' => $sectionText,
                    'word_count' => str_word_count($sectionText),
                ];
            }

            $fullText = trim($fullText);

            if (empty($fullText)) {
                return ParseResult::failure(
                    'No text content could be extracted from the document.',
                    $this->getDriverName()
                );
            }

            // Get document properties
            $properties = $phpWord->getDocInfo();

            $metrics = [
                'sections' => count($sections),
                'words' => str_word_count($fullText),
                'characters' => strlen($fullText),
                'tables' => count($tables),
                'headings' => count($headings),
                'title' => $properties->getTitle() ?: null,
                'author' => $properties->getCreator() ?: null,
                'created' => $properties->getCreated() ? date('Y-m-d H:i:s', $properties->getCreated()) : null,
                'modified' => $properties->getModified() ? date('Y-m-d H:i:s', $properties->getModified()) : null,
            ];

            $structuredData = [
                'sections' => $this->buildSectionHierarchy($headings, $fullText),
                'headings' => $headings,
                'tables' => $tables,
            ];

            // Page map is harder for DOCX - approximate based on word count
            $pageMap = $this->estimatePageMap($sections);

            return ParseResult::success(
                extractedText: $fullText,
                structuredData: $structuredData,
                pageMap: $pageMap,
                metrics: $metrics,
                driverUsed: $this->getDriverName(),
            );
        } catch (\Exception $e) {
            return ParseResult::failure(
                "DOCX parsing failed: {$e->getMessage()}",
                $this->getDriverName()
            );
        }
    }

    public function getDriverName(): string
    {
        return 'docx';
    }

    public function getPriority(): int
    {
        return 100; // High priority for DOCX files
    }

    /**
     * Extract text from a PHPWord section.
     */
    protected function extractSectionText(Section $section, array &$headings, array &$tables): string
    {
        $text = '';

        foreach ($section->getElements() as $element) {
            $text .= $this->extractElementText($element, $headings, $tables) . "\n";
        }

        return trim($text);
    }

    /**
     * Extract text from a PHPWord element.
     */
    protected function extractElementText($element, array &$headings, array &$tables): string
    {
        $text = '';

        // Handle Text elements
        if ($element instanceof Text) {
            return $element->getText();
        }

        // Handle TextRun (paragraph with formatting)
        if ($element instanceof TextRun) {
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $text .= $child->getText();
                }
            }

            // Check if this is a heading based on style
            $paragraphStyle = $element->getParagraphStyle();
            if ($paragraphStyle) {
                $styleName = is_string($paragraphStyle) ? $paragraphStyle : '';
                if (preg_match('/^Heading(\d+)$/i', $styleName, $matches)) {
                    $headings[] = [
                        'text' => trim($text),
                        'level' => (int) $matches[1],
                    ];
                }
            }

            return $text;
        }

        // Handle Tables
        if ($element instanceof Table) {
            $tableData = $this->extractTableData($element);
            $tables[] = $tableData;

            // Convert table to text representation
            foreach ($tableData['rows'] as $row) {
                $text .= implode(' | ', $row) . "\n";
            }

            return $text;
        }

        // Handle other elements that might contain text
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $text .= $this->extractElementText($child, $headings, $tables);
            }
        }

        if (method_exists($element, 'getText')) {
            return $element->getText();
        }

        return $text;
    }

    /**
     * Extract data from a table element.
     */
    protected function extractTableData(Table $table): array
    {
        $data = [
            'rows' => [],
            'columns' => 0,
        ];

        foreach ($table->getRows() as $row) {
            $rowData = [];
            foreach ($row->getCells() as $cell) {
                $cellText = '';
                foreach ($cell->getElements() as $element) {
                    $dummyHeadings = [];
                    $dummyTables = [];
                    $cellText .= $this->extractElementText($element, $dummyHeadings, $dummyTables);
                }
                $rowData[] = trim($cellText);
            }
            $data['rows'][] = $rowData;
            $data['columns'] = max($data['columns'], count($rowData));
        }

        return $data;
    }

    /**
     * Build section hierarchy from headings.
     */
    protected function buildSectionHierarchy(array $headings, string $fullText): array
    {
        if (empty($headings)) {
            return [];
        }

        $sections = [];

        foreach ($headings as $index => $heading) {
            $nextHeading = $headings[$index + 1] ?? null;

            // Find content between this heading and the next
            $startPos = strpos($fullText, $heading['text']);
            if ($startPos === false) {
                continue;
            }

            $startPos += strlen($heading['text']);

            if ($nextHeading) {
                $endPos = strpos($fullText, $nextHeading['text'], $startPos);
                $content = $endPos !== false
                    ? substr($fullText, $startPos, $endPos - $startPos)
                    : substr($fullText, $startPos);
            } else {
                $content = substr($fullText, $startPos);
            }

            $sections[] = [
                'title' => $heading['text'],
                'level' => $heading['level'],
                'content' => trim($content),
            ];
        }

        return $sections;
    }

    /**
     * Estimate page mapping based on word count.
     * Assumes ~300 words per page as a rough estimate.
     */
    protected function estimatePageMap(array $sections): array
    {
        $pageMap = [];
        $currentPage = 1;
        $wordsOnCurrentPage = 0;
        $wordsPerPage = 300;

        foreach ($sections as $section) {
            $words = str_word_count($section['text']);

            while ($words > 0) {
                $wordsNeeded = $wordsPerPage - $wordsOnCurrentPage;

                if ($words <= $wordsNeeded) {
                    if (! isset($pageMap[$currentPage])) {
                        $pageMap[$currentPage] = ['page' => $currentPage, 'text' => '', 'word_count' => 0];
                    }
                    $pageMap[$currentPage]['text'] .= $section['text'] . "\n";
                    $pageMap[$currentPage]['word_count'] += $words;
                    $wordsOnCurrentPage += $words;
                    $words = 0;
                } else {
                    // This section spans multiple pages
                    $currentPage++;
                    $wordsOnCurrentPage = 0;
                }
            }
        }

        return array_values($pageMap);
    }
}

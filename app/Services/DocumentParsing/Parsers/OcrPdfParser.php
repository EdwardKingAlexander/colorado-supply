<?php

namespace App\Services\DocumentParsing\Parsers;

use App\Contracts\DocumentParserInterface;
use App\Models\ContractDocument;
use App\Services\DocumentParsing\ParseResult;
use Imagick;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrPdfParser implements DocumentParserInterface
{
    protected string $tempDir;

    protected int $dpi = 300;

    public function __construct()
    {
        $this->tempDir = storage_path('app/temp/ocr');

        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    public function canParse(ContractDocument $document): bool
    {
        // Only handle PDFs
        if (! str_contains($document->mime_type, 'pdf')) {
            return false;
        }

        // Check if Imagick is available
        if (! extension_loaded('imagick')) {
            logger()->debug('OcrPdfParser: Imagick extension not available');

            return false;
        }

        // Check if Tesseract is available
        if (! $this->isTesseractAvailable()) {
            logger()->debug('OcrPdfParser: Tesseract not available');

            return false;
        }

        return true;
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

            // Generate unique prefix for temp files
            $prefix = uniqid('ocr_');
            $imageFiles = [];

            try {
                // Convert PDF pages to images
                $imageFiles = $this->convertPdfToImages($filePath, $prefix);

                if (empty($imageFiles)) {
                    return ParseResult::failure(
                        'Failed to convert PDF to images.',
                        $this->getDriverName()
                    );
                }

                // OCR each page
                $pageTexts = [];
                $fullText = '';

                foreach ($imageFiles as $pageNumber => $imagePath) {
                    $pageText = $this->ocrImage($imagePath);
                    $pageTexts[$pageNumber] = $pageText;
                    $fullText .= $pageText . "\n\n--- Page {$pageNumber} ---\n\n";
                }

                $fullText = trim($fullText);

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

                $metrics = [
                    'pages' => count($pageTexts),
                    'words' => str_word_count($fullText),
                    'characters' => strlen($fullText),
                    'ocr_engine' => 'tesseract',
                    'dpi' => $this->dpi,
                ];

                // Basic structure extraction
                $structuredData = $this->extractStructure($fullText);

                return ParseResult::success(
                    extractedText: $fullText,
                    structuredData: $structuredData,
                    pageMap: $pageMap,
                    metrics: $metrics,
                    driverUsed: $this->getDriverName(),
                );
            } finally {
                // Clean up temp files
                $this->cleanupTempFiles($imageFiles);
            }
        } catch (\Exception $e) {
            return ParseResult::failure(
                "OCR parsing failed: {$e->getMessage()}",
                $this->getDriverName()
            );
        }
    }

    public function getDriverName(): string
    {
        return 'ocr_tesseract';
    }

    public function getPriority(): int
    {
        return 50; // Lower priority - try native PDF first
    }

    /**
     * Check if Tesseract is available on the system.
     */
    protected function isTesseractAvailable(): bool
    {
        try {
            $tesseract = new TesseractOCR;
            $tesseract->executable();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert PDF pages to images using Imagick.
     *
     * @return array<int, string> Page number => image path
     */
    protected function convertPdfToImages(string $pdfPath, string $prefix): array
    {
        $images = [];

        $imagick = new Imagick;
        $imagick->setResolution($this->dpi, $this->dpi);
        $imagick->readImage($pdfPath);

        $pageCount = $imagick->getNumberImages();

        for ($i = 0; $i < $pageCount; $i++) {
            $imagick->setIteratorIndex($i);

            // Convert to grayscale for better OCR
            $imagick->setImageType(Imagick::IMGTYPE_GRAYSCALE);

            // Enhance for OCR
            $imagick->normalizeImage();
            $imagick->enhanceImage();

            // Save as PNG
            $imagePath = "{$this->tempDir}/{$prefix}_page_{$i}.png";
            $imagick->setImageFormat('png');
            $imagick->writeImage($imagePath);

            $images[$i + 1] = $imagePath;
        }

        $imagick->clear();
        $imagick->destroy();

        return $images;
    }

    /**
     * Run OCR on an image file.
     */
    protected function ocrImage(string $imagePath): string
    {
        $ocr = new TesseractOCR($imagePath);

        // Configure for best quality
        $ocr->lang('eng');
        $ocr->psm(3); // Fully automatic page segmentation
        $ocr->oem(3); // Default - LSTM + Legacy together

        return $ocr->run();
    }

    /**
     * Clean up temporary image files.
     */
    protected function cleanupTempFiles(array $imageFiles): void
    {
        foreach ($imageFiles as $imagePath) {
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
    }

    /**
     * Extract basic structure from OCR text.
     */
    protected function extractStructure(string $text): array
    {
        $structure = [
            'sections' => [],
            'headings' => [],
        ];

        $lines = explode("\n", $text);

        foreach ($lines as $lineNumber => $line) {
            $trimmedLine = trim($line);

            if (empty($trimmedLine)) {
                continue;
            }

            // Skip page markers
            if (preg_match('/^---\s*Page\s+\d+\s*---$/', $trimmedLine)) {
                continue;
            }

            // Detect headings (similar logic to NativePdfParser)
            if ($this->detectHeading($trimmedLine)) {
                $structure['headings'][] = [
                    'text' => $trimmedLine,
                    'line' => $lineNumber + 1,
                    'level' => $this->guessHeadingLevel($trimmedLine),
                ];
            }
        }

        return $structure;
    }

    /**
     * Detect if a line is likely a heading.
     */
    protected function detectHeading(string $line): bool
    {
        if (strlen($line) > 100) {
            return false;
        }

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

        if (preg_match('/^[A-Z\s\-\d\.]+$/', $line) && strlen($line) > 3 && strlen($line) < 80) {
            $letters = preg_replace('/[^a-zA-Z]/', '', $line);
            $uppercase = preg_replace('/[^A-Z]/', '', $line);

            if (strlen($letters) > 0 && strlen($uppercase) / strlen($letters) > 0.8) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guess heading level.
     */
    protected function guessHeadingLevel(string $heading): int
    {
        if (preg_match('/^(?:PART|SECTION)\s+/i', $heading)) {
            return 1;
        }
        if (preg_match('/^ARTICLE\s+/i', $heading)) {
            return 2;
        }
        if (preg_match('/^(\d+)\.(\d*)/', $heading)) {
            return 3;
        }

        return 4;
    }
}

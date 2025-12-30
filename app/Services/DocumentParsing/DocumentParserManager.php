<?php

namespace App\Services\DocumentParsing;

use App\Contracts\DocumentParserInterface;
use App\Models\ContractDocument;
use App\Models\DocumentArtifact;
use App\Models\DocumentParseRun;
use App\Services\DocumentParsing\Parsers\DocxParser;
use App\Services\DocumentParsing\Parsers\NativePdfParser;
use App\Services\DocumentParsing\Parsers\OcrPdfParser;

class DocumentParserManager
{
    /** @var DocumentParserInterface[] */
    protected array $parsers = [];

    public function __construct()
    {
        $this->registerDefaultParsers();
    }

    /**
     * Register the default parsers.
     */
    protected function registerDefaultParsers(): void
    {
        $this->register(new NativePdfParser);
        $this->register(new DocxParser);
        $this->register(new OcrPdfParser);
    }

    /**
     * Register a parser.
     */
    public function register(DocumentParserInterface $parser): void
    {
        $this->parsers[] = $parser;

        // Sort by priority (highest first)
        usort($this->parsers, fn ($a, $b) => $b->getPriority() - $a->getPriority());
    }

    /**
     * Parse a document using the appropriate parser.
     */
    public function parse(ContractDocument $document): ParseResult
    {
        // Find a parser that can handle this document
        $parser = $this->findParser($document);

        if (! $parser) {
            return ParseResult::failure(
                "No parser available for file type: {$document->mime_type}"
            );
        }

        // Create a parse run record
        $parseRun = DocumentParseRun::create([
            'contract_document_id' => $document->id,
            'parser_driver' => $parser->getDriverName(),
            'status' => DocumentParseRun::STATUS_PENDING,
            'checksum_at_parse' => $document->checksum,
        ]);

        $parseRun->markAsRunning();

        try {
            // Execute the parse
            $result = $parser->parse($document);

            if ($result->success) {
                // Store artifacts
                $this->storeArtifacts($document, $parseRun, $result);

                // Update parse run
                $parseRun->markAsCompleted($result->metrics ?? []);

                // Update document status
                $document->update([
                    'status' => ContractDocument::STATUS_PARSED,
                    'page_count' => $result->getPageCount(),
                ]);

                // Log the action
                $document->logAction('parsed', [
                    'driver' => $parser->getDriverName(),
                    'pages' => $result->getPageCount(),
                    'words' => $result->getWordCount(),
                ]);
            } else {
                $parseRun->markAsFailed($result->errorMessage ?? 'Unknown error');

                // If native PDF failed, try OCR
                if ($parser->getDriverName() === 'native_pdf') {
                    return $this->tryFallbackParser($document, $parseRun);
                }

                // Update document status
                $document->update([
                    'status' => ContractDocument::STATUS_FAILED,
                ]);

                $document->logAction('parse_failed', [
                    'driver' => $parser->getDriverName(),
                    'error' => $result->errorMessage,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $parseRun->markAsFailed($e->getMessage());

            $document->update([
                'status' => ContractDocument::STATUS_FAILED,
            ]);

            $document->logAction('parse_failed', [
                'driver' => $parser->getDriverName(),
                'error' => $e->getMessage(),
            ]);

            return ParseResult::failure($e->getMessage(), $parser->getDriverName());
        }
    }

    /**
     * Try fallback parser (OCR for PDFs).
     */
    protected function tryFallbackParser(ContractDocument $document, DocumentParseRun $failedRun): ParseResult
    {
        $ocrParser = $this->findParserByDriver('ocr_tesseract');

        if (! $ocrParser || ! $ocrParser->canParse($document)) {
            return ParseResult::failure(
                'Native PDF parsing failed and OCR is not available.',
                'native_pdf'
            );
        }

        // Create new parse run for OCR
        $parseRun = DocumentParseRun::create([
            'contract_document_id' => $document->id,
            'parser_driver' => $ocrParser->getDriverName(),
            'status' => DocumentParseRun::STATUS_PENDING,
            'checksum_at_parse' => $document->checksum,
        ]);

        $parseRun->markAsRunning();

        try {
            $result = $ocrParser->parse($document);

            if ($result->success) {
                $this->storeArtifacts($document, $parseRun, $result);
                $parseRun->markAsCompleted($result->metrics ?? []);

                $document->update([
                    'status' => ContractDocument::STATUS_PARSED,
                    'page_count' => $result->getPageCount(),
                ]);

                $document->logAction('parsed', [
                    'driver' => 'ocr_tesseract',
                    'fallback' => true,
                    'pages' => $result->getPageCount(),
                    'words' => $result->getWordCount(),
                ]);
            } else {
                $parseRun->markAsFailed($result->errorMessage ?? 'OCR failed');

                $document->update([
                    'status' => ContractDocument::STATUS_FAILED,
                ]);

                $document->logAction('parse_failed', [
                    'driver' => 'ocr_tesseract',
                    'error' => $result->errorMessage,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $parseRun->markAsFailed($e->getMessage());

            return ParseResult::failure($e->getMessage(), 'ocr_tesseract');
        }
    }

    /**
     * Find a parser that can handle the document.
     */
    protected function findParser(ContractDocument $document): ?DocumentParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->canParse($document)) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * Find a parser by driver name.
     */
    protected function findParserByDriver(string $driver): ?DocumentParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->getDriverName() === $driver) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * Store parse result artifacts.
     */
    protected function storeArtifacts(ContractDocument $document, DocumentParseRun $parseRun, ParseResult $result): void
    {
        // Store extracted text
        if ($result->extractedText) {
            $artifact = DocumentArtifact::create([
                'contract_document_id' => $document->id,
                'parse_run_id' => $parseRun->id,
                'artifact_type' => DocumentArtifact::TYPE_EXTRACTED_TEXT,
                'storage_disk' => 'local',
                'storage_path' => "artifacts/{$document->id}/extracted_text.txt",
            ]);

            // Store in database for quick access (text is usually not too large)
            $artifact->storeContent($result->extractedText, storeInDatabase: true);
        }

        // Store structured data
        if ($result->structuredData) {
            $artifact = DocumentArtifact::create([
                'contract_document_id' => $document->id,
                'parse_run_id' => $parseRun->id,
                'artifact_type' => DocumentArtifact::TYPE_STRUCTURED_JSON,
                'storage_disk' => 'local',
                'storage_path' => "artifacts/{$document->id}/structured.json",
            ]);

            $artifact->storeContent(json_encode($result->structuredData, JSON_PRETTY_PRINT), storeInDatabase: true);
        }

        // Store page map
        if ($result->pageMap) {
            $artifact = DocumentArtifact::create([
                'contract_document_id' => $document->id,
                'parse_run_id' => $parseRun->id,
                'artifact_type' => DocumentArtifact::TYPE_PAGE_MAP,
                'storage_disk' => 'local',
                'storage_path' => "artifacts/{$document->id}/page_map.json",
            ]);

            $artifact->storeContent(json_encode($result->pageMap, JSON_PRETTY_PRINT), storeInDatabase: true);
        }
    }

    /**
     * Get all registered parsers.
     *
     * @return DocumentParserInterface[]
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * Check which parsers are available for a document.
     *
     * @return DocumentParserInterface[]
     */
    public function getAvailableParsers(ContractDocument $document): array
    {
        return array_filter($this->parsers, fn ($p) => $p->canParse($document));
    }
}

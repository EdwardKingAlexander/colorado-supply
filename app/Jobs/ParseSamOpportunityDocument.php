<?php

namespace App\Jobs;

use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentParse;
use App\Services\SamDocumentParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class ParseSamOpportunityDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentId;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(SamDocumentParser $parser): void
    {
        $document = SamOpportunityDocument::find($this->documentId);

        if (! $document) {
            return;
        }

        $result = $parser->parse($document);

        $parse = SamOpportunityDocumentParse::firstOrNew([
            'sam_opportunity_document_id' => $document->id,
        ]);

        $parse->status = $result['status'] ?? 'failed';
        $parse->raw_text = $result['raw_text'] ?? null;
        $parse->error_message = $result['error_message'] ?? null;
        $parse->parsed_at = $result['status'] === 'success' ? Date::now() : null;
        $parse->save();

        if ($parse->status === 'success') {
            ChunkSamOpportunityDocument::dispatch($document->id);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ParseSamOpportunityDocument failed', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);

        $parse = SamOpportunityDocumentParse::firstOrNew([
            'sam_opportunity_document_id' => $this->documentId,
        ]);

        $parse->status = 'failed';
        $parse->raw_text = null;
        $parse->error_message = $exception->getMessage();
        $parse->parsed_at = null;
        $parse->save();
    }
}

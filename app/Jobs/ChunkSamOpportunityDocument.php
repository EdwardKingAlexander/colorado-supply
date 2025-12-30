<?php

namespace App\Jobs;

use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentChunk;
use App\Services\SamDocumentChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChunkSamOpportunityDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentId;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(SamDocumentChunker $chunker): void
    {
        $document = SamOpportunityDocument::with('parse')->find($this->documentId);
        if (! $document || ! $document->parse || $document->parse->status !== 'success') {
            return;
        }

        // Idempotency: clear existing chunks for this document
        SamOpportunityDocumentChunk::where('sam_opportunity_document_id', $document->id)->delete();

        $chunks = $chunker->chunk($document->parse);

        foreach ($chunks as $payload) {
            SamOpportunityDocumentChunk::create([
                'sam_opportunity_document_id' => $document->id,
                'chunk_index' => $payload['chunk_index'],
                'text' => $payload['text'],
                'token_count' => $payload['token_count'],
            ]);
        }

        // After chunks are created, enqueue embeddings
        if (! empty($chunks)) {
            EmbedSamOpportunityDocumentChunks::dispatch($document->id);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ChunkSamOpportunityDocument failed', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);
    }
}

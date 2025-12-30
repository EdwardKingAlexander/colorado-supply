<?php

namespace App\Jobs;

use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentChunk;
use App\Models\SamOpportunityDocumentEmbedding;
use App\Services\SamDocumentEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmbedSamOpportunityDocumentChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentId;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(SamDocumentEmbeddingService $embedder): void
    {
        $document = SamOpportunityDocument::find($this->documentId);
        if (! $document) {
            return;
        }

        $chunks = SamOpportunityDocumentChunk::where('sam_opportunity_document_id', $document->id)->get();

        foreach ($chunks as $chunk) {
            $alreadyEmbedded = $chunk->embeddings()
                ->where('embedding_model', 'stub')
                ->exists();

            if ($alreadyEmbedded) {
                continue;
            }

            $result = $embedder->embed($chunk->text);

            SamOpportunityDocumentEmbedding::create([
                'sam_opportunity_document_chunk_id' => $chunk->id,
                'embedding_model' => $result['embedding_model'],
                'vector' => $result['vector'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EmbedSamOpportunityDocumentChunks failed', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);
    }
}

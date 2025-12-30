<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EmbedSamOpportunityDocumentChunks;
use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentChunk;
use App\Models\SamOpportunityDocumentParse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmbedSamOpportunityDocumentChunksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('sam_opportunities')) {
            Schema::create('sam_opportunities', function ($table) {
                $table->id();
                $table->string('title')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_creates_stub_embeddings_for_chunks(): void
    {
        $opp = \App\Models\SamOpportunity::create(['title' => 'Embed']);
        $document = SamOpportunityDocument::create([
            'sam_opportunity_id' => $opp->id,
            'uploaded_by_user_id' => null,
            'storage_path' => 'sam_documents/1/file.txt',
            'disk' => 'sam_documents',
            'original_filename' => 'file.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 10,
        ]);
        SamOpportunityDocumentParse::create([
            'sam_opportunity_document_id' => $document->id,
            'status' => 'success',
            'raw_text' => 'sample text',
        ]);

        $chunk = SamOpportunityDocumentChunk::create([
            'sam_opportunity_document_id' => $document->id,
            'chunk_index' => 0,
            'text' => 'sample text',
            'token_count' => 3,
        ]);

        (new EmbedSamOpportunityDocumentChunks($document->id))->handle(app('App\Services\SamDocumentEmbeddingService'));

        $this->assertDatabaseHas('sam_opportunity_document_embeddings', [
            'sam_opportunity_document_chunk_id' => $chunk->id,
            'embedding_model' => 'stub',
        ]);
    }
}

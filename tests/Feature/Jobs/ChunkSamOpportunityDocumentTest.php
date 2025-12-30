<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ChunkSamOpportunityDocument;
use App\Jobs\EmbedSamOpportunityDocumentChunks;
use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentParse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChunkSamOpportunityDocumentTest extends TestCase
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

    public function test_creates_chunks_for_successful_parse_and_enqueues_embeddings(): void
    {
        Queue::fake([EmbedSamOpportunityDocumentChunks::class]);

        $opp = \App\Models\SamOpportunity::create(['title' => 'Chunked']);
        $document = SamOpportunityDocument::create([
            'sam_opportunity_id' => $opp->id,
            'uploaded_by_user_id' => null,
            'storage_path' => 'sam_documents/1/file.txt',
            'disk' => 'sam_documents',
            'original_filename' => 'file.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 1500,
        ]);
        SamOpportunityDocumentParse::create([
            'sam_opportunity_document_id' => $document->id,
            'status' => 'success',
            'raw_text' => str_repeat('A', 1500),
        ]);

        (new ChunkSamOpportunityDocument($document->id))->handle(app('App\Services\SamDocumentChunker'));

        $this->assertDatabaseCount('sam_opportunity_document_chunks', 2);
        $this->assertDatabaseHas('sam_opportunity_document_chunks', [
            'sam_opportunity_document_id' => $document->id,
            'chunk_index' => 0,
        ]);

        Queue::assertPushed(EmbedSamOpportunityDocumentChunks::class);
    }

    public function test_skips_when_parse_not_success(): void
    {
        $opp = \App\Models\SamOpportunity::create(['title' => 'Skip']);
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
            'status' => 'failed',
            'raw_text' => null,
        ]);

        (new ChunkSamOpportunityDocument($document->id))->handle(app('App\Services\SamDocumentChunker'));

        $this->assertDatabaseCount('sam_opportunity_document_chunks', 0);
    }
}

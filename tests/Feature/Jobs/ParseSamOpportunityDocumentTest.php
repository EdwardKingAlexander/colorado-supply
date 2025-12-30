<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ChunkSamOpportunityDocument;
use App\Jobs\ParseSamOpportunityDocument;
use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentParse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParseSamOpportunityDocumentTest extends TestCase
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

    public function test_parses_text_file_and_enqueues_chunking(): void
    {
        Queue::fake([ChunkSamOpportunityDocument::class]);
        Storage::fake('sam_documents');

        $document = SamOpportunityDocument::factory()->create([
            'disk' => 'sam_documents',
            'storage_path' => 'sam_documents/1/file.txt',
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('sam_documents')->put('sam_documents/1/file.txt', 'hello world');

        (new ParseSamOpportunityDocument($document->id))->handle(app('App\Services\SamDocumentParser'));

        $this->assertDatabaseHas('sam_opportunity_document_parses', [
            'sam_opportunity_document_id' => $document->id,
            'status' => 'success',
        ]);

        Queue::assertPushed(ChunkSamOpportunityDocument::class);
    }

    public function test_marks_failed_for_unsupported_mime(): void
    {
        Storage::fake('sam_documents');
        $document = SamOpportunityDocument::factory()->create([
            'disk' => 'sam_documents',
            'storage_path' => 'sam_documents/1/file.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('sam_documents')->put('sam_documents/1/file.pdf', 'fake');

        (new ParseSamOpportunityDocument($document->id))->handle(app('App\Services\SamDocumentParser'));

        $this->assertDatabaseHas('sam_opportunity_document_parses', [
            'sam_opportunity_document_id' => $document->id,
            'status' => 'failed',
        ]);
    }
}

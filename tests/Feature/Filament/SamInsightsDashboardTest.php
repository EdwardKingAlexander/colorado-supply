<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SamInsightsDashboard;
use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentChunk;
use App\Models\SamOpportunityDocumentEmbedding;
use App\Models\SamOpportunityDocumentParse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SamInsightsDashboardTest extends TestCase
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

    public function test_dashboard_loads_for_filament_user(): void
    {
        $user = User::factory()->create();
        $opp = SamOpportunity::create(['title' => 'Test']);
        $doc = SamOpportunityDocument::create([
            'sam_opportunity_id' => $opp->id,
            'uploaded_by_user_id' => $user->id,
            'storage_path' => 'sam_documents/1/file.txt',
            'disk' => 'sam_documents',
            'original_filename' => 'file.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 10,
        ]);

        SamOpportunityDocumentParse::create([
            'sam_opportunity_document_id' => $doc->id,
            'status' => 'success',
            'raw_text' => 'text',
        ]);

        $chunk = SamOpportunityDocumentChunk::create([
            'sam_opportunity_document_id' => $doc->id,
            'chunk_index' => 0,
            'text' => 'text',
            'token_count' => 2,
        ]);

        SamOpportunityDocumentEmbedding::create([
            'sam_opportunity_document_chunk_id' => $chunk->id,
            'embedding_model' => 'stub',
            'vector' => [0.1, 0.2],
        ]);

        $this->actingAs($user);

        $this->get(SamInsightsDashboard::getUrl())->assertStatus(200);
    }
}

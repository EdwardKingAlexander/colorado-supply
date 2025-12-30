<?php

namespace Tests\Feature\Api;

use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentChunk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SamOpportunityRagQueryApiTest extends TestCase
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

    public function test_requires_authentication(): void
    {
        $opp = SamOpportunity::create(['title' => 'Test Opp']);
        $this->postJson("/api/v1/sam-opportunities/{$opp->id}/rag-query", ['query' => 'hello'])
            ->assertUnauthorized();
    }

    public function test_returns_stubbed_answer_with_chunks(): void
    {
        $user = \App\Models\User::factory()->create();
        $opp = SamOpportunity::create(['title' => 'Has Chunks']);

        $doc = SamOpportunityDocument::create([
          'sam_opportunity_id' => $opp->id,
          'uploaded_by_user_id' => $user->id,
          'storage_path' => 'sam_documents/1/file.txt',
          'disk' => 'sam_documents',
          'original_filename' => 'file.txt',
          'mime_type' => 'text/plain',
          'size_bytes' => 10,
        ]);

        SamOpportunityDocumentChunk::create([
            'sam_opportunity_document_id' => $doc->id,
            'chunk_index' => 0,
            'text' => 'This is a relevant chunk about testing.',
            'token_count' => 7,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sam-opportunities/{$opp->id}/rag-query", ['query' => 'testing'])
            ->assertOk()
            ->json();

        $this->assertNotEmpty($response['top_chunks']);
        $this->assertNotEmpty($response['answer']);
        $this->assertEquals($opp->id, $response['opportunity_id']);
    }

    public function test_handles_no_chunks_gracefully(): void
    {
        $user = \App\Models\User::factory()->create();
        $opp = SamOpportunity::create(['title' => 'Empty']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sam-opportunities/{$opp->id}/rag-query", ['query' => 'test'])
            ->assertOk()
            ->json();

        $this->assertEmpty($response['top_chunks']);
        $this->assertStringContainsString('No indexed content', $response['answer']);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SamOpportunityDocumentsApiTest extends TestCase
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

    public function test_guest_cannot_access_document_endpoints(): void
    {
        $opp = SamOpportunity::create(['title' => 'Doc Test']);

        $this->getJson("/api/v1/sam-opportunities/{$opp->id}/documents")->assertUnauthorized();
        $this->postJson("/api/v1/sam-opportunities/{$opp->id}/documents")->assertUnauthorized();
        $this->deleteJson("/api/v1/sam-opportunities/{$opp->id}/documents/1")->assertUnauthorized();
    }

    public function test_user_can_upload_and_list_documents(): void
    {
        Queue::fake();
        Storage::fake('sam_documents');
        $user = User::factory()->create();
        $opp = SamOpportunity::create(['title' => 'Upload Doc']);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $uploadResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sam-opportunities/{$opp->id}/documents", [
                'file' => $file,
            ])
            ->assertCreated()
            ->json();

        $this->assertDatabaseHas('sam_opportunity_documents', [
            'sam_opportunity_id' => $opp->id,
            'uploaded_by_user_id' => $user->id,
            'original_filename' => 'test.pdf',
        ]);

        Storage::disk('sam_documents')->assertExists($uploadResponse['storage_path']);

        Queue::assertPushed(\App\Jobs\ParseSamOpportunityDocument::class);

        $listResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/sam-opportunities/{$opp->id}/documents")
            ->assertOk()
            ->json('data');

        $this->assertTrue(collect($listResponse)->pluck('id')->contains($uploadResponse['id']));
    }

    public function test_rejects_invalid_mime_type(): void
    {
        Storage::fake('sam_documents');
        $user = User::factory()->create();
        $opp = SamOpportunity::create(['title' => 'Invalid Mime']);

        $file = UploadedFile::fake()->create('bad.exe', 10, 'application/octet-stream');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sam-opportunities/{$opp->id}/documents", [
                'file' => $file,
            ])
            ->assertStatus(422);
    }

    public function test_delete_removes_record_and_file(): void
    {
        Storage::fake('sam_documents');
        $user = User::factory()->create();
        $opp = SamOpportunity::create(['title' => 'Delete Doc']);

        $file = UploadedFile::fake()->create('delete.pdf', 50, 'application/pdf');

        $upload = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sam-opportunities/{$opp->id}/documents", [
                'file' => $file,
            ])
            ->json();

        $doc = SamOpportunityDocument::first();
        Storage::disk('sam_documents')->assertExists($doc->storage_path);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/sam-opportunities/{$opp->id}/documents/{$doc->id}")
            ->assertOk()
            ->assertJson(['deleted' => true]);

        Storage::disk('sam_documents')->assertMissing($doc->storage_path);
        $this->assertDatabaseCount('sam_opportunity_documents', 0);
    }
}

<?php

namespace Tests\Feature\Models;

use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SamOpportunityDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('sam_opportunities')) {
            Schema::create('sam_opportunities', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_document_can_be_linked_to_opportunity_and_user(): void
    {
        $opp = SamOpportunity::create(['title' => 'Test Opp']);
        $user = User::factory()->create();

        $doc = SamOpportunityDocument::create([
            'sam_opportunity_id' => $opp->id,
            'uploaded_by_user_id' => $user->id,
            'storage_path' => 'sam_documents/1/file.pdf',
            'disk' => SamOpportunityDocument::DEFAULT_DISK,
            'original_filename' => 'file.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $this->assertEquals($opp->id, $doc->opportunity->id);
        $this->assertEquals($user->id, $doc->uploadedBy->id);
    }

    public function test_opportunity_documents_relationship(): void
    {
        $opp = SamOpportunity::create(['title' => 'Docs Opp']);

        $docA = SamOpportunityDocument::create([
            'sam_opportunity_id' => $opp->id,
            'storage_path' => 'sam_documents/1/a.pdf',
            'disk' => SamOpportunityDocument::DEFAULT_DISK,
            'original_filename' => 'a.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
        ]);

        $docB = SamOpportunityDocument::create([
            'sam_opportunity_id' => $opp->id,
            'storage_path' => 'sam_documents/1/b.pdf',
            'disk' => SamOpportunityDocument::DEFAULT_DISK,
            'original_filename' => 'b.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 4096,
        ]);

        $this->assertCount(2, $opp->documents);
        $this->assertTrue($opp->documents->pluck('id')->contains($docA->id));
        $this->assertTrue($opp->documents->pluck('id')->contains($docB->id));
    }

    public function test_cascade_delete_removes_documents(): void
    {
        $opp = SamOpportunity::create(['title' => 'Cascade Opp']);

        SamOpportunityDocument::create([
            'sam_opportunity_id' => $opp->id,
            'storage_path' => 'sam_documents/1/c.pdf',
            'disk' => SamOpportunityDocument::DEFAULT_DISK,
            'original_filename' => 'c.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $this->assertDatabaseCount('sam_opportunity_documents', 1);

        $opp->delete();

        $this->assertDatabaseCount('sam_opportunity_documents', 0);
    }
}

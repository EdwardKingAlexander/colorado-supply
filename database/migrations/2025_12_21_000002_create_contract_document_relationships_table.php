<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_document_relationships', function (Blueprint $table) {
            $table->id();

            // The document being modified/referenced (e.g., the original RFP)
            $table->foreignId('parent_document_id')
                ->constrained('contract_documents', indexName: 'doc_rel_parent_fk')
                ->cascadeOnDelete();

            // The modifying/referencing document (e.g., an amendment)
            $table->foreignId('child_document_id')
                ->constrained('contract_documents', indexName: 'doc_rel_child_fk')
                ->cascadeOnDelete();

            // Relationship type
            $table->string('relationship_type'); // amends, supersedes, references, attachment_of

            // When the relationship takes effect (important for amendments)
            $table->date('effective_date')->nullable();

            // For amendments, track the sequence number (Amendment 0001, 0002, etc.)
            $table->unsignedInteger('amendment_number')->nullable();

            // Human notes about the relationship
            $table->text('notes')->nullable();

            // Who created this relationship
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('admins', indexName: 'doc_rel_creator_fk')
                ->nullOnDelete();

            $table->timestamps();

            // Prevent duplicate relationships
            $table->unique(
                ['parent_document_id', 'child_document_id', 'relationship_type'],
                'unique_document_relationship'
            );

            // Indexes for common queries
            $table->index('relationship_type', 'doc_rel_type_idx');
            $table->index('effective_date', 'doc_rel_eff_date_idx');
            $table->index(['parent_document_id', 'relationship_type'], 'doc_rel_parent_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_document_relationships');
    }
};

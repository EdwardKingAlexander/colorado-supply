<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_documents', function (Blueprint $table) {
            $table->id();

            // Self-reference for document versions (replaces separate versions table)
            $table->foreignId('parent_document_id')
                ->nullable()
                ->constrained('contract_documents', indexName: 'cdocs_parent_fk')
                ->nullOnDelete();

            // Link to SAM opportunity (optional - documents can exist independently)
            $table->foreignId('sam_opportunity_id')
                ->nullable()
                ->constrained('sam_opportunities', indexName: 'cdocs_opp_fk')
                ->nullOnDelete();

            // File information
            $table->string('original_filename');
            $table->string('mime_type');
            $table->string('storage_disk')->default('contract_documents');
            $table->string('storage_path');
            $table->string('checksum', 64); // SHA-256 hash
            $table->unsignedBigInteger('file_size_bytes');

            // Document metadata
            $table->unsignedInteger('page_count')->nullable();
            $table->string('document_type')->nullable(); // rfp, rfq, ifb, amendment, attachment, etc.

            // Processing status
            $table->string('status')->default('pending'); // pending, processing, parsed, failed, archived

            // CUI (Controlled Unclassified Information) detection
            $table->boolean('cui_detected')->default(false);
            $table->json('cui_categories')->nullable(); // Array of detected CUI categories

            // Upload tracking
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('admins', indexName: 'cdocs_uploader_fk')
                ->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();

            // Error tracking
            $table->text('error_message')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['sam_opportunity_id', 'created_at'], 'cdocs_opp_created_idx');
            $table->index('status', 'cdocs_status_idx');
            $table->index('checksum', 'cdocs_checksum_idx');
            $table->index('document_type', 'cdocs_type_idx');
            $table->index('cui_detected', 'cdocs_cui_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_documents');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_artifacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_document_id')
                ->constrained('contract_documents', indexName: 'artifacts_doc_fk')
                ->cascadeOnDelete();

            $table->foreignId('parse_run_id')
                ->constrained('document_parse_runs', indexName: 'artifacts_run_fk')
                ->cascadeOnDelete();

            // Artifact type
            $table->string('artifact_type'); // extracted_text, structured_json, page_map, section_chunks, etc.

            // Storage location
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');

            // Integrity
            $table->string('checksum')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();

            // For text artifacts, store directly in DB for quick access
            $table->longText('content')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('artifact_type', 'artifacts_type_idx');
            $table->index(['contract_document_id', 'artifact_type'], 'artifacts_doc_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_artifacts');
    }
};

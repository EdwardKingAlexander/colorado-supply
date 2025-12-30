<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sam_opportunity_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sam_opportunity_document_id')
                ->constrained('sam_opportunity_documents', indexName: 'sam_doc_chunks_doc_id_fk')
                ->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('text');
            $table->unsignedInteger('token_count')->default(0);
            $table->timestamps();

            $table->index(['sam_opportunity_document_id', 'chunk_index'], 'sam_doc_chunks_doc_id_chunk_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_opportunity_document_chunks');
    }
};

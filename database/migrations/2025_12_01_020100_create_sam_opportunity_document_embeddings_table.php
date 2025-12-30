<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sam_opportunity_document_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sam_opportunity_document_chunk_id')
                ->constrained('sam_opportunity_document_chunks', indexName: 'sam_doc_embeddings_chunk_id_fk')
                ->cascadeOnDelete();
            $table->string('embedding_model')->default('stub');
            $table->json('vector');
            $table->timestamps();

            $table->index('sam_opportunity_document_chunk_id', 'sam_doc_embeddings_chunk_id_idx');
            $table->index('embedding_model', 'sam_doc_embeddings_model_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_opportunity_document_embeddings');
    }
};

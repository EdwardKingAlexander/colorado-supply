<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sam_opportunity_document_parses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sam_opportunity_document_id')
                ->constrained('sam_opportunity_documents', indexName: 'sam_doc_parses_doc_id_fk')
                ->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->longText('raw_text')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->unique('sam_opportunity_document_id', 'sam_doc_parses_doc_id_unique');
            $table->index('sam_opportunity_document_id', 'sam_doc_parses_doc_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_opportunity_document_parses');
    }
};

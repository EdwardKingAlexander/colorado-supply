<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_parse_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_document_id')
                ->constrained('contract_documents', indexName: 'parse_runs_doc_fk')
                ->cascadeOnDelete();

            // Parser driver used
            $table->string('parser_driver'); // native_pdf, docx, ocr_tesseract, etc.

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Status tracking
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->text('error_message')->nullable();

            // Metrics from parsing
            $table->json('metrics')->nullable(); // {pages, words, tables, sections, images}

            // Checksum at time of parse (for invalidation if document changes)
            $table->string('checksum_at_parse')->nullable();

            // Whether this is the active/current parse for the document
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('status', 'parse_runs_status_idx');
            $table->index('parser_driver', 'parse_runs_driver_idx');
            $table->index(['contract_document_id', 'is_active'], 'parse_runs_doc_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_parse_runs');
    }
};

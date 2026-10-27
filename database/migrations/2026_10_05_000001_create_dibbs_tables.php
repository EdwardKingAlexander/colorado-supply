<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DIBBS RFQ ingestion (ai/modules/dibbs-rfq-tracking, Phase 2).
 *
 * RFQ data is public DLA data and global (no company_id); company-specific
 * tracking, matching and quoting come in later phases on their own tables.
 * Layouts and keys come from findings.md (verified 2026-10-04).
 */
return new class extends Migration
{
    public function up(): void
    {
        // One row per downloaded or uploaded file. sha256 makes imports idempotent.
        Schema::create('dibbs_files', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64); // e.g. in261002.txt, bq261002.zip
            $table->string('type', 8); // 'in' (RFQ index) | 'bq' (batch quote + approved source zip)
            $table->date('posting_date')->nullable();
            $table->string('source', 16); // 'automated' | 'upload'
            $table->string('sha256', 64)->unique();
            $table->unsignedInteger('size_bytes');
            $table->string('stored_path');
            $table->string('status', 16)->default('pending'); // pending | imported | failed
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('row_errors')->nullable(); // first N row-level problems
            $table->text('error')->nullable(); // file-level failure
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'posting_date']);
            $table->index('name');
        });

        // One row per RFQ line: unique on (solicitation, purchase request).
        Schema::create('dibbs_rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('solicitation_number', 13);
            $table->string('purchase_request', 13);
            $table->string('nsn', 46); // NSN (13 digits) or part number, per item_type
            $table->char('item_type', 1); // 1 = NSN, 2 = part number
            $table->char('fsc', 4)->nullable(); // first 4 NSN digits
            $table->char('niin', 9)->nullable(); // last 9 NSN digits
            $table->string('nomenclature', 32)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('unit_of_issue', 2)->nullable();
            $table->date('return_by_date')->nullable();
            $table->string('document_file', 19)->nullable();
            $table->string('buyer_code', 5)->nullable();
            $table->char('amsc', 1)->nullable();
            $table->char('set_aside', 1)->nullable(); // Y H R L A E N
            $table->unsignedSmallInteger('set_aside_percent')->nullable();
            $table->boolean('automated_award')->default(false); // solicitation char 9 = T|U
            $table->string('raw_record', 140);
            $table->foreignId('first_seen_file_id')->nullable()->constrained('dibbs_files')->nullOnDelete();
            $table->foreignId('last_seen_file_id')->nullable()->constrained('dibbs_files')->nullOnDelete();
            $table->timestamps();

            $table->unique(['solicitation_number', 'purchase_request']);
            $table->index('nsn');
            $table->index('fsc');
            $table->index('return_by_date');
            $table->index('amsc');
        });

        // Approved sources per NSN (as*.txt inside bq zips).
        Schema::create('dibbs_approved_sources', function (Blueprint $table) {
            $table->id();
            $table->string('nsn', 46);
            $table->string('cage_code', 5);
            $table->string('part_number', 64);
            $table->string('company_name')->nullable();
            $table->foreignId('dibbs_file_id')->nullable()->constrained('dibbs_files')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nsn', 'cage_code', 'part_number']);
            $table->index('cage_code');
        });

        // DLA's pre-filled batch quote rows (bq*.txt), preserved verbatim for export (Phase 6).
        Schema::create('dibbs_quote_templates', function (Blueprint $table) {
            $table->id();
            $table->string('solicitation_number', 13);
            $table->string('purchase_request', 13);
            $table->unsignedSmallInteger('row_index')->default(0); // >1 row per key happens
            $table->foreignId('dibbs_rfq_id')->nullable()->constrained('dibbs_rfqs')->nullOnDelete();
            $table->string('line_number', 4)->nullable(); // column 44
            $table->unsignedInteger('quantity')->nullable(); // column 49
            $table->unsignedSmallInteger('delivery_days')->nullable(); // column 51
            $table->text('raw_row');
            $table->foreignId('dibbs_file_id')->nullable()->constrained('dibbs_files')->nullOnDelete();
            $table->timestamps();

            $table->unique(['solicitation_number', 'purchase_request', 'row_index'], 'dibbs_quote_templates_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dibbs_quote_templates');
        Schema::dropIfExists('dibbs_approved_sources');
        Schema::dropIfExists('dibbs_rfqs');
        Schema::dropIfExists('dibbs_files');
    }
};

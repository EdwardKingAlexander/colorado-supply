<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Private NSN sourcing cases (ai/modules/nsn-sourcing-workflow, phase 4).
 *
 * Every table carries company_id; children copy it from their case. Catalog
 * references (import, reference, NIIN, part, CAGE) are stored as plain values
 * with no foreign keys because the PUB LOG catalog may live on another
 * connection, and a case must stay readable if a release is pruned.
 * Actors are type + id because Admin and User have separate ID spaces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nsn_sourcing_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('pub_log_import_id');
            $table->unsignedBigInteger('nsn_reference_id')->nullable();
            $table->date('release_date')->nullable();
            $table->string('nsn', 13);
            $table->string('niin', 9);
            $table->string('part_number');
            $table->string('part_search_key');
            $table->string('cage_code', 5);
            $table->string('item_name')->nullable();
            $table->foreignId('mil_spec_part_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('open');
            $table->string('production_state', 24)->default('unknown');
            $table->date('follow_up_on')->nullable();
            $table->string('opened_by_type');
            $table->unsignedBigInteger('opened_by_id');
            $table->timestamp('closed_at')->nullable();
            $table->string('close_reason')->nullable();
            // Set while open so only one open case exists per company + reference pair.
            $table->string('open_key')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'open_key']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'niin']);
        });

        Schema::create('nsn_cage_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nsn_sourcing_case_id')->constrained()->cascadeOnDelete();
            $table->string('cage_code', 5);
            $table->string('outcome', 16);
            $table->string('verified_name')->nullable();
            $table->text('verified_address')->nullable();
            $table->string('verified_status')->nullable();
            $table->string('source_url', 2048);
            $table->text('notes')->nullable();
            $table->date('verified_on');
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('nsn_sales_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nsn_sourcing_case_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('website', 2048)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('stated_role')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('source_url', 2048);
            $table->date('source_on');
            $table->text('notes')->nullable();
            $table->foreignId('vendor_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->timestamps();
        });

        Schema::create('nsn_sourcing_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nsn_sourcing_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nsn_sales_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 16);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamp('contacted_at')->nullable();
            $table->string('contacted_by_type')->nullable();
            $table->unsignedBigInteger('contacted_by_id')->nullable();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->timestamps();
        });

        Schema::create('nsn_sourcing_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nsn_sourcing_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nsn_sourcing_inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supersedes_response_id')->nullable()->constrained('nsn_sourcing_responses')->restrictOnDelete();
            $table->date('received_on');
            $table->string('channel', 16);
            $table->string('responder')->nullable();
            $table->text('body');
            $table->string('source_url', 2048)->nullable();
            $table->string('production_state', 24);
            $table->string('replacement_nsn', 13)->nullable();
            $table->string('replacement_part_number')->nullable();
            $table->string('minimum_order')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('sells_direct', 8)->default('unknown');
            $table->json('distributors')->nullable();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('nsn_sourcing_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nsn_sourcing_response_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['nsn_sourcing_files', 'nsn_sourcing_responses', 'nsn_sourcing_inquiries', 'nsn_sales_contacts', 'nsn_cage_verifications', 'nsn_sourcing_cases'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

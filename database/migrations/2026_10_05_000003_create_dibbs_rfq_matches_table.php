<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why an RFQ is worth a look for the company (ai/modules/dibbs-rfq-tracking, Phase 4).
 *
 * Rebuilt wholesale by DibbsRfqMatcher; never edited by hand. Two reasons:
 * - nsn_catalog: the RFQ's NSN is a MilSpecPart we carry (score 100);
 * - approved_source: a Manufacturer/Supplier we buy from is a DLA-approved
 *   source (CAGE) for the NSN (score 70).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dibbs_rfq_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dibbs_rfq_id')->constrained('dibbs_rfqs')->cascadeOnDelete();
            $table->string('reason', 24);
            $table->morphs('matchable'); // MilSpecPart, Manufacturer or Supplier
            $table->string('cage_code', 5)->nullable();
            $table->string('part_number', 64)->nullable(); // the approved part, for approved_source
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(['company_id', 'dibbs_rfq_id', 'reason', 'matchable_type', 'matchable_id'], 'dibbs_rfq_matches_unique');
            $table->index(['company_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dibbs_rfq_matches');
    }
};

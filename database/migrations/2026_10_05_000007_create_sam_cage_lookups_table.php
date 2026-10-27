<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local cache of SAM.gov Entity API answers by CAGE code (ai/modules/dibbs-rfq-tracking,
 * Phase 8). Keeps lookups within the entity API's small daily quota.
 * (Distinct from the PUB LOG catalog's `cage_entities` snapshot table.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sam_cage_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('cage_code', 5)->unique();
            $table->boolean('found')->default(false);
            $table->string('legal_business_name')->nullable();
            $table->string('dba_name')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 8)->nullable();
            $table->string('country', 8)->nullable();
            $table->string('uei', 12)->nullable();
            $table->string('registration_status', 32)->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_cage_lookups');
    }
};

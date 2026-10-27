<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raw DIBBS files are pruned after a retention window while their database
 * rows stay as the import record (ai/modules/dibbs-rfq-tracking, Phase 7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dibbs_files', function (Blueprint $table) {
            $table->string('stored_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dibbs_files', function (Blueprint $table) {
            $table->string('stored_path')->nullable(false)->change();
        });
    }
};

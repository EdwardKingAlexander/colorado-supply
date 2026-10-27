<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records when a posting day's digest went out, so it is never sent twice
 * (ai/modules/dibbs-rfq-tracking, Phase 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dibbs_files', function (Blueprint $table) {
            $table->timestamp('digest_sent_at')->nullable()->after('imported_at');
        });
    }

    public function down(): void
    {
        Schema::table('dibbs_files', function (Blueprint $table) {
            $table->dropColumn('digest_sent_at');
        });
    }
};

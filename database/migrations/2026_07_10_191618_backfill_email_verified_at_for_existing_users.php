<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grandfather users who registered before email verification shipped:
     * they were never asked to verify, so enforcement must not lock them
     * out. Idempotent — only touches rows still unverified.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    /**
     * Irreversible by design: we cannot distinguish backfilled rows from
     * genuinely verified ones afterwards, and un-verifying real users
     * would lock them out.
     */
    public function down(): void
    {
        //
    }
};

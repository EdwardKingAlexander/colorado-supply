<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('mcp_settings') || ! Schema::hasColumn('mcp_settings', 'value')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite stores JSON columns as TEXT already, so nothing to change.
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `mcp_settings` MODIFY `value` MEDIUMTEXT NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "mcp_settings" ALTER COLUMN "value" TYPE TEXT');

            return;
        }

        Schema::table('mcp_settings', function (Blueprint $table) {
            $table->text('value')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('mcp_settings') || ! Schema::hasColumn('mcp_settings', 'value')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `mcp_settings` MODIFY `value` JSON NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "mcp_settings" ALTER COLUMN "value" TYPE JSONB USING "value"::jsonb');

            return;
        }

        Schema::table('mcp_settings', function (Blueprint $table) {
            $table->json('value')->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sam_opportunities', function (Blueprint $table) {
            $table->string('solicitation_number')->nullable()->after('notice_id');

            $table->index('solicitation_number');
        });
    }

    public function down(): void
    {
        Schema::table('sam_opportunities', function (Blueprint $table) {
            $table->dropIndex(['solicitation_number']);
            $table->dropColumn('solicitation_number');
        });
    }
};

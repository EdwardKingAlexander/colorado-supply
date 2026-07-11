<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->text('address')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};

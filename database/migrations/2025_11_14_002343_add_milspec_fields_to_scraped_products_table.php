<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scraped_products', function (Blueprint $table) {
            $table->string('nsn')->nullable()->index()->after('sku'); // National Stock Number
            $table->string('cage_code', 5)->nullable()->index()->after('nsn'); // CAGE code
            $table->string('milspec')->nullable()->index()->after('cage_code'); // Mil-spec designation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraped_products', function (Blueprint $table) {
            $table->dropColumn(['nsn', 'cage_code', 'milspec']);
        });
    }
};

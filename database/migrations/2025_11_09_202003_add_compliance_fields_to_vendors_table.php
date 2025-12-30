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
        Schema::table('vendors', function (Blueprint $table) {
            // SAM Registration
            $table->date('sam_expiration_date')->nullable();
            $table->string('sam_number')->nullable();

            // W-9 Form
            $table->date('w9_date')->nullable();
            $table->string('tax_id')->nullable();

            // Insurance Certificate
            $table->date('insurance_expiration_date')->nullable();
            $table->string('insurance_policy_number')->nullable();

            // CAGE Code
            $table->string('cage_code')->nullable();

            // DUNS Number
            $table->string('duns_number')->nullable();

            // NAICS Code
            $table->string('naics_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'sam_expiration_date',
                'sam_number',
                'w9_date',
                'tax_id',
                'insurance_expiration_date',
                'insurance_policy_number',
                'cage_code',
                'duns_number',
                'naics_code',
            ]);
        });
    }
};

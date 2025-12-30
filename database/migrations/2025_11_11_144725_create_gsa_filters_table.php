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
        Schema::create('gsa_filters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type', ['naics', 'psc']);
            $table->string('code');
            $table->string('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['type', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gsa_filters');
    }
};

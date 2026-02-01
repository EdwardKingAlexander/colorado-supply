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
        Schema::create('mil_spec_parts', function (Blueprint $table) {
            $table->id();
            $table->string('nsn')->unique();
            $table->text('description');
            $table->string('manufacturer_part_number')->nullable();
            $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mil_spec_parts');
    }
};

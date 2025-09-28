<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Product FK (indexed automatically by FK)
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // Attribute fields
            $table->string('name', 120);
            $table->enum('type', ['string', 'integer', 'float', 'boolean', 'select'])
                ->default('string')
                ->index();
            $table->string('value', 255)->nullable();

            // Prevent duplicate names per product
            $table->unique(['product_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};

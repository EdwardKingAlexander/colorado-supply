<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional product-page data: quantity price breaks, gallery images, related
 * products/substitutes, and the government-buyer flag that gates GSA/contract
 * pricing (ai/modules/product-detail-page, Phase 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();

            $table->unique(['product_id', 'min_quantity']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 20);
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id', 'type']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_government_buyer')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_government_buyer');
        });

        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_price_tiers');
    }
};

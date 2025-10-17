<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Product reference (optional - may not have products table yet)
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Item details (snapshot)
            $table->string('sku')->nullable();
            $table->string('name');
            $table->text('description')->nullable();

            // Quantities
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_discount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0); // computed: (qty * unit_price) - line_discount

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

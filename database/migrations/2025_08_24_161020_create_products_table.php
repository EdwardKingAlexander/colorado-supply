<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('vendor_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('sku', 100);
            $table->string('mpn', 100)->nullable();
            $table->string('gtin', 14)->nullable();

            $table->text('description')->nullable();
            $table->string('image')->nullable();

            $table->decimal('cost', 12, 4)->nullable();
            $table->decimal('list_price', 12, 2)->nullable();
            $table->decimal('price', 12, 2)->nullable();

            $table->unsignedInteger('stock')->nullable();
            $table->unsignedSmallInteger('reorder_point')->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('weight_g')->nullable();
            $table->unsignedInteger('length_mm')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();

            $table->string('unspsc', 10)->nullable();
            $table->string('psc_fsc', 4)->nullable();
            $table->string('country_of_origin', 2)->nullable();

            $table->json('meta')->nullable();

            $table->unique(['vendor_id', 'sku']);
            $table->unique(['vendor_id', 'slug']);
            $table->index(['vendor_id', 'category_id']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

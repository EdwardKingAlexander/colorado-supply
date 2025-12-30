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
        Schema::create('scraped_products', function (Blueprint $table) {
            $table->id();
            $table->string('source_url')->index();
            $table->string('vendor_domain')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('price')->nullable();
            $table->decimal('price_numeric', 10, 2)->nullable()->index();
            $table->text('html_cache_path')->nullable();
            $table->json('raw_data')->nullable();
            $table->string('status')->default('pending')->index(); // pending, imported, failed, ignored
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('imported_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->text('import_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraped_products');
    }
};

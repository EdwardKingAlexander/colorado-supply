<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('sku')->nullable();
            $table->string('name');
            $table->decimal('qty', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_subtotal', 12, 2)->default(0.00);
            $table->decimal('line_tax', 12, 2)->default(0.00);
            $table->decimal('line_total', 12, 2)->default(0.00);

            $table->string('uom')->nullable(); // unit of measure
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // Add check constraints only for MySQL (SQLite doesn't support ALTER TABLE ADD CONSTRAINT for CHECK)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE quote_items ADD CONSTRAINT quote_items_qty_positive CHECK (qty > 0)');
            DB::statement('ALTER TABLE quote_items ADD CONSTRAINT quote_items_unit_price_nonnegative CHECK (unit_price >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};

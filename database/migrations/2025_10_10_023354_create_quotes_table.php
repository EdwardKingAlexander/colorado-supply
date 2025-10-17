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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->enum('status', ['draft', 'sent', 'ordered', 'cancelled', 'expired'])->default('draft');
            $table->index('status');

            // Customer relationship (nullable for walk-in)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->index('customer_id');

            // Walk-in ("cash/card") fields
            $table->string('walk_in_label')->default('cash/card');
            $table->string('walk_in_org')->nullable();
            $table->string('walk_in_contact_name')->nullable();
            $table->string('walk_in_email')->nullable();
            $table->string('walk_in_phone')->nullable();
            $table->json('walk_in_billing_json')->nullable();
            $table->json('walk_in_shipping_json')->nullable();

            // Currency and financials
            $table->string('currency', 3)->default('USD');
            $table->decimal('tax_rate', 5, 2)->default(0.00); // percentage
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('tax_total', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);

            // Sales rep relationship
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->index('sales_rep_id');

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};

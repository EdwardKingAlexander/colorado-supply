<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();

            // Customer (optional - can be a saved customer or cash/card guest)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Quote linkage (optional)
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();

            // Cash/Card guest fields (when customer_id is null)
            $table->string('cash_card_name')->nullable();
            $table->string('cash_card_email')->nullable();
            $table->string('cash_card_phone')->nullable();
            $table->string('cash_card_company')->nullable();

            // Contact info snapshot
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('company_name')->nullable();

            // Billing address snapshot
            $table->json('billing_address')->nullable();

            // Shipping address snapshot
            $table->json('shipping_address')->nullable();

            // Commercial fields
            $table->string('po_number')->nullable();
            $table->string('job_number')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            // Totals (stored in cents for precision, display as dollars)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            // Tax rate used
            $table->decimal('tax_rate', 5, 2)->default(0); // e.g., 8.50 for 8.5%

            // Statuses
            $table->string('status')->default('draft'); // draft|confirmed|cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid|pending|paid|refunded|failed
            $table->string('fulfillment_status')->default('unfulfilled'); // unfulfilled|partially_fulfilled|fulfilled|returned

            // Dates
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Metadata
            $table->json('meta')->nullable(); // Store allowed payment methods, etc.

            // Ownership
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('order_number');
            $table->index('customer_id');
            $table->index('quote_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('fulfillment_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

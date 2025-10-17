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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->enum('payment_method', ['credit_card', 'debit_card', 'online_portal']);
            $table->string('po_number')->nullable();
            $table->string('job_number')->nullable();
            $table->decimal('order_total', 12, 2)->default(0.00);
            $table->string('status')->default('created');

            // Walk-in details copied from quote if applicable
            $table->string('walk_in_label')->nullable();
            $table->string('walk_in_org')->nullable();
            $table->string('walk_in_contact_name')->nullable();
            $table->string('walk_in_email')->nullable();
            $table->string('walk_in_phone')->nullable();
            $table->json('walk_in_billing_json')->nullable();
            $table->json('walk_in_shipping_json')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

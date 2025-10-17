<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Payment details
            $table->string('method'); // card|cash|check|wire|other
            $table->string('status')->default('pending'); // pending|paid|failed|refunded
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Gateway references
            $table->string('gateway')->nullable(); // stripe|paypal|etc
            $table->string('gateway_payment_intent_id')->nullable();
            $table->string('gateway_charge_id')->nullable();
            $table->string('gateway_session_id')->nullable();
            $table->string('gateway_refund_id')->nullable();

            // Failure details
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            // Dates
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('status');
            $table->index('gateway_payment_intent_id');
            $table->index('gateway_charge_id');
            $table->index('gateway_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

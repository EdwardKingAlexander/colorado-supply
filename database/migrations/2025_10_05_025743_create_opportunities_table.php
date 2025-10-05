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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->decimal('probability_override', 5, 2)->nullable();
            $table->date('expected_close_date')->nullable();
            $table->enum('status', ['open', 'won', 'lost'])->default('open');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('source')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->foreignId('lost_reason_id')->nullable()->constrained('lost_reasons')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pipeline_id', 'stage_id']);
            $table->index(['owner_id', 'status']);
            $table->index('expected_close_date');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};

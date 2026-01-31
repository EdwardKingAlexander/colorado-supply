<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category');
            $table->date('due_date');
            $table->string('recurrence')->default('once');
            $table->json('recurrence_rule')->nullable();
            $table->json('reminder_days')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('related_document_id')->nullable()->constrained('business_documents')->nullOnDelete();
            $table->string('external_url')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('due_date');
            $table->index('recurrence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_deadlines');
    }
};

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
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 255)->index();
            $table->string('phone', 30)->nullable();
            $table->string('company', 150)->nullable();
            $table->string('equipment_type', 100);
            $table->string('manufacturer', 100)->nullable();
            $table->string('model_number', 100);
            $table->string('serial_number', 100)->nullable();
            $table->text('issue_description');
            $table->string('urgency', 20)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            // lifecycle
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};

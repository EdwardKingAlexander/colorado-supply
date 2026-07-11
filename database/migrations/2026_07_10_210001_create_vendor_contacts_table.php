<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile_phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();

            $table->index(['vendor_id', 'is_preferred']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_contacts');
    }
};

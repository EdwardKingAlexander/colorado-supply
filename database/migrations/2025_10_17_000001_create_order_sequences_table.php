<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('period', 6)->unique(); // YYYYMM
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_sequences');
    }
};

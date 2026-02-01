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
        Schema::create('procurement_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mil_spec_part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2); // Assuming price up to 99,999,999.99
            $table->integer('quantity');
            $table->date('acquisition_date');
            $table->string('source_url');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_histories');
    }
};

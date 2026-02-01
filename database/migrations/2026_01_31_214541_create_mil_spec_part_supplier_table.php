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
        if (Schema::hasTable('mil_spec_part_supplier')) {
            return;
        }

        Schema::create('mil_spec_part_supplier', function (Blueprint $table) {
            $table->foreignId('mil_spec_part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_part_number')->nullable();
            $table->primary(['mil_spec_part_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mil_spec_part_supplier');
    }
};

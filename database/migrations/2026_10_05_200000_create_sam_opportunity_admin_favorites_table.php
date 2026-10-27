<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sam_opportunity_admin_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sam_opportunity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['admin_id', 'sam_opportunity_id'], 'sam_admin_favorite_unique');
            $table->index('sam_opportunity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_opportunity_admin_favorites');
    }
};

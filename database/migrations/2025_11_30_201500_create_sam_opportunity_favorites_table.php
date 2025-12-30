<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sam_opportunity_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sam_opportunity_id');
            $table->timestamps();
            $table->unique(['user_id', 'sam_opportunity_id']);
            $table->index('sam_opportunity_id');
        });

        if (Schema::hasTable('sam_opportunities')) {
            Schema::table('sam_opportunity_favorites', function (Blueprint $table) {
                $table->foreign('sam_opportunity_id')
                    ->references('id')
                    ->on('sam_opportunities')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_opportunity_favorites');
    }
};

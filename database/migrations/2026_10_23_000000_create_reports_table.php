<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // The owner's company when saved; who it is shared with.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('dataset', 40);
            $table->json('definition');
            $table->unsignedSmallInteger('schema_version');
            $table->string('visibility', 20)->default('private');
            $table->boolean('company_can_edit')->default(false);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

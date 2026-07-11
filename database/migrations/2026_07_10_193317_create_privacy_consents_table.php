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
        Schema::create('privacy_consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_uuid');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('categories');
            $table->boolean('gpc_applied')->default(false);
            $table->string('policy_version', 32);
            // Hashed for accountability without retaining raw PII
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['visitor_uuid', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privacy_consents');
    }
};

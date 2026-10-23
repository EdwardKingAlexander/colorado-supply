<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin notification choices (ai/modules/will-call-notifications-complete, phase 2).
 * A missing row means "on": admins are subscribed to everything by default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained()->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('channel', 20);
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->unique(['admin_id', 'type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_preferences');
    }
};

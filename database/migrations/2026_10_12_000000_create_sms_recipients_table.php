<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Text message recipients (ai/modules/will-call-notifications-complete, phase 6):
 * one verified, consented phone number per user or admin, with the consent
 * record and any STOP opt-out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_recipients', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('phone_e164', 16)->index();
            $table->timestamp('consented_at');
            $table->string('consent_ip', 45)->nullable();
            $table->string('consent_text_version', 20);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->string('verification_code_hash')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->unsignedTinyInteger('verification_attempts')->default(0);
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_recipients');
    }
};

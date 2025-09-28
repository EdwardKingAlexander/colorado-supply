<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 255)->index();
            $table->string('phone', 30)->nullable();
            $table->text('message'); // validated to 2000 chars, but store as text
            $table->string('ip', 45)->nullable();         // IPv4/IPv6
            $table->text('user_agent')->nullable();

            // lifecycle
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // in case you want to “undo” deletes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};

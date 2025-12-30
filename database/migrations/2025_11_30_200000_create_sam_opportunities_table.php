<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sam_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('notice_id')->unique()->nullable();
            $table->string('title');
            $table->string('agency')->nullable();
            $table->date('response_deadline')->nullable();
            $table->text('description')->nullable();
            $table->string('notice_type')->nullable();
            $table->string('naics_code')->nullable();
            $table->string('psc_code')->nullable();
            $table->string('set_aside')->nullable();
            $table->string('place_of_performance')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('posted_date')->nullable();
            $table->timestamp('last_modified_date')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['response_deadline', 'created_at']);
            $table->index('agency');
            $table->index('notice_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_opportunities');
    }
};

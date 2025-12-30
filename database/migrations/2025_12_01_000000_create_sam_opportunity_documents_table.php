<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sam_opportunity_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sam_opportunity_id')->constrained('sam_opportunities', indexName: 'sam_docs_opp_id_fk')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users', indexName: 'sam_docs_user_id_fk')->nullOnDelete();
            $table->string('storage_path');
            $table->string('disk')->default('sam_documents');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['sam_opportunity_id', 'created_at'], 'sam_docs_opp_id_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sam_opportunity_documents');
    }
};

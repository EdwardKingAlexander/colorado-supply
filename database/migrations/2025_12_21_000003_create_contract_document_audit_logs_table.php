<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_document_audit_logs', function (Blueprint $table) {
            $table->id();

            // The document being acted upon
            $table->foreignId('contract_document_id')
                ->constrained('contract_documents', indexName: 'audit_doc_fk')
                ->cascadeOnDelete();

            // Who performed the action
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('admins', indexName: 'audit_admin_fk')
                ->nullOnDelete();

            // Action type
            $table->string('action'); // uploaded, viewed, downloaded, parsed, reviewed, updated, deleted, etc.

            // Additional context
            $table->json('metadata')->nullable(); // Flexible storage for action-specific data

            // What changed (for update actions)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('action', 'audit_action_idx');
            $table->index('created_at', 'audit_created_idx');
            $table->index(['contract_document_id', 'created_at'], 'audit_doc_time_idx');
            $table->index(['admin_id', 'created_at'], 'audit_admin_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_document_audit_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The company's pursuit of a DIBBS RFQ (ai/modules/dibbs-rfq-tracking, Phase 3).
 *
 * Created lazily, the first time someone acts on an RFQ: untouched RFQs have no
 * row and read as "new". Quote fields are filled in Phase 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dibbs_rfq_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dibbs_rfq_id')->constrained('dibbs_rfqs')->cascadeOnDelete();
            $table->string('status', 16)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->decimal('unit_price', 14, 4)->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'dibbs_rfq_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dibbs_rfq_trackings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DIBBS batch quote export (ai/modules/dibbs-rfq-tracking, Phase 6).
 *
 * dibbs_quoting_profiles: the company's standing representations, entered and
 * confirmed by a person (never defaulted by the app). Column numbers refer to
 * DLA's batch quote format (/refs/help/Quoting/Batch/BatchFormat.aspx).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dibbs_quoting_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('quoter_cage', 5)->nullable();                 // col 6
            $table->string('quote_for_cage', 5)->nullable();              // col 7
            $table->char('small_business_code', 1)->nullable();          // col 13
            $table->string('affirmative_action_code', 2)->nullable();    // col 21
            $table->string('previous_contracts_code', 2)->nullable();    // col 22
            $table->char('adr_code', 1)->nullable();                      // col 23
            $table->char('child_labor_code', 1)->nullable();             // col 120
            $table->char('default_hazardous_material', 1)->nullable();   // col 65 default
            $table->char('default_material_code', 1)->nullable();        // col 67 default
            $table->string('default_manufacturer_dealer', 2)->nullable(); // col 102 default
            $table->foreignId('confirmed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('dibbs_rfq_trackings', function (Blueprint $table) {
            $table->string('manufacturer_dealer', 2)->nullable()->after('delivery_days');       // col 102
            $table->string('manufacturing_source_cage', 5)->nullable()->after('manufacturer_dealer'); // col 103
            $table->char('part_offered_code', 1)->nullable()->after('manufacturing_source_cage');    // col 106
            $table->string('part_offered_cage', 5)->nullable()->after('part_offered_code');         // col 107
            $table->string('part_offered_number', 40)->nullable()->after('part_offered_cage');      // col 108
            $table->char('quality_code', 1)->nullable()->after('part_offered_number');              // col 118
            $table->char('hazardous_material', 1)->nullable()->after('quality_code');               // col 65
            $table->char('material_code', 1)->nullable()->after('hazardous_material');              // col 67
            $table->timestamp('exported_at')->nullable()->after('quoted_at');
            $table->string('export_reference', 64)->nullable()->after('exported_at');
            $table->json('quote_snapshot')->nullable()->after('export_reference');
        });
    }

    public function down(): void
    {
        Schema::table('dibbs_rfq_trackings', function (Blueprint $table) {
            $table->dropColumn([
                'manufacturer_dealer', 'manufacturing_source_cage', 'part_offered_code', 'part_offered_cage',
                'part_offered_number', 'quality_code', 'hazardous_material', 'material_code',
                'exported_at', 'export_reference', 'quote_snapshot',
            ]);
        });

        Schema::dropIfExists('dibbs_quoting_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Military Specification Fields
            $table->string('mil_spec_number', 100)->nullable()->after('mpn');
            $table->enum('standard_type', [
                'Commercial',
                'AN',           // Army-Navy
                'MS',           // Military Standard
                'NAS',          // National Aerospace Standard
                'MIL-DTL',      // Military Detail Specification
                'MIL-PRF',      // Military Performance Specification
                'MIL-C',        // Military Capacitor Specification
                'MIL-R',        // Military Resistor Specification
                'MIL-T',        // Military Transformer Specification
                'MIL-W',        // Military Wire Specification
                'Other',
            ])->default('Commercial')->after('mil_spec_number');

            // Government Identifiers
            $table->string('nsn', 13)->nullable()->after('standard_type')
                ->comment('National Stock Number (13 digits)');
            $table->string('naics_code', 6)->nullable()->after('psc_fsc');
            $table->string('cage_code', 5)->nullable()->after('naics_code')
                ->comment('Commercial and Government Entity Code');
            $table->string('part_number', 100)->nullable()->after('cage_code')
                ->comment('Original manufacturer part number');

            // Product Classification
            $table->string('upc', 12)->nullable()->after('gtin');
            $table->string('ean', 13)->nullable()->after('upc');
            $table->string('unit_of_measure', 20)->default('EA')->after('ean');
            $table->unsignedInteger('minimum_order_quantity')->default(1)->after('unit_of_measure');

            // Compliance & Certifications
            $table->boolean('is_hazmat')->default(false)->after('is_active');
            $table->boolean('export_controlled')->default(false)->after('is_hazmat');
            $table->boolean('berry_compliant')->default(false)->after('export_controlled')
                ->comment('Berry Amendment compliant (US manufactured)');
            $table->boolean('taa_compliant')->default(false)->after('berry_compliant')
                ->comment('Trade Agreements Act compliant');
            $table->boolean('made_in_usa')->default(false)->after('taa_compliant');
            $table->boolean('gsa_approved')->default(false)->after('made_in_usa')
                ->comment('On GSA Schedule');
            $table->boolean('qpl_listed')->default(false)->after('gsa_approved')
                ->comment('Qualified Products List');

            // Additional Product Information
            $table->string('material', 100)->nullable()->after('description');
            $table->string('finish', 100)->nullable()->after('material');
            $table->text('specifications')->nullable()->after('finish')
                ->comment('Detailed technical specifications');
            $table->string('datasheet_url', 500)->nullable()->after('image');
            $table->string('msds_url', 500)->nullable()->after('datasheet_url')
                ->comment('Material Safety Data Sheet URL');

            // Pricing Tiers
            $table->decimal('gsa_price', 12, 2)->nullable()->after('price')
                ->comment('GSA Schedule pricing');
            $table->decimal('contract_price', 12, 2)->nullable()->after('gsa_price')
                ->comment('Contract-specific pricing');

            // Search optimization
            $table->text('search_keywords')->nullable()->after('specifications')
                ->comment('Additional keywords for search');

            // Indexes for performance
            $table->index('mil_spec_number');
            $table->index('nsn');
            $table->index('standard_type');
            $table->index('naics_code');
            $table->index('cage_code');
            $table->index(['gsa_approved', 'is_active']);
            $table->index(['berry_compliant', 'taa_compliant', 'made_in_usa']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['mil_spec_number']);
            $table->dropIndex(['nsn']);
            $table->dropIndex(['standard_type']);
            $table->dropIndex(['naics_code']);
            $table->dropIndex(['cage_code']);
            $table->dropIndex(['gsa_approved', 'is_active']);
            $table->dropIndex(['berry_compliant', 'taa_compliant', 'made_in_usa']);

            $table->dropColumn([
                'mil_spec_number',
                'standard_type',
                'nsn',
                'naics_code',
                'cage_code',
                'part_number',
                'upc',
                'ean',
                'unit_of_measure',
                'minimum_order_quantity',
                'is_hazmat',
                'export_controlled',
                'berry_compliant',
                'taa_compliant',
                'made_in_usa',
                'gsa_approved',
                'qpl_listed',
                'material',
                'finish',
                'specifications',
                'datasheet_url',
                'msds_url',
                'gsa_price',
                'contract_price',
                'search_keywords',
            ]);
        });
    }
};

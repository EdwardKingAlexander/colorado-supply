<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns with safety checks (idempotent)
        Schema::table('products', function (Blueprint $table) {
            // Military Specification Fields
            if (! Schema::hasColumn('products', 'mil_spec_number')) {
                $table->string('mil_spec_number', 100)->nullable();
            }
            if (! Schema::hasColumn('products', 'standard_type')) {
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
                ])->default('Commercial');
            }

            // Government Identifiers
            if (! Schema::hasColumn('products', 'nsn')) {
                $table->string('nsn', 13)->nullable()
                    ->comment('National Stock Number (13 digits)');
            }
            if (! Schema::hasColumn('products', 'naics_code')) {
                $table->string('naics_code', 6)->nullable();
            }
            if (! Schema::hasColumn('products', 'cage_code')) {
                $table->string('cage_code', 5)->nullable()
                    ->comment('Commercial and Government Entity Code');
            }
            if (! Schema::hasColumn('products', 'part_number')) {
                $table->string('part_number', 100)->nullable()
                    ->comment('Original manufacturer part number');
            }

            // Product Classification
            if (! Schema::hasColumn('products', 'upc')) {
                $table->string('upc', 12)->nullable();
            }
            if (! Schema::hasColumn('products', 'ean')) {
                $table->string('ean', 13)->nullable();
            }
            if (! Schema::hasColumn('products', 'unit_of_measure')) {
                $table->string('unit_of_measure', 20)->default('EA');
            }
            if (! Schema::hasColumn('products', 'minimum_order_quantity')) {
                $table->unsignedInteger('minimum_order_quantity')->default(1);
            }

            // Compliance & Certifications
            if (! Schema::hasColumn('products', 'is_hazmat')) {
                $table->boolean('is_hazmat')->default(false);
            }
            if (! Schema::hasColumn('products', 'export_controlled')) {
                $table->boolean('export_controlled')->default(false);
            }
            if (! Schema::hasColumn('products', 'berry_compliant')) {
                $table->boolean('berry_compliant')->default(false)
                    ->comment('Berry Amendment compliant (US manufactured)');
            }
            if (! Schema::hasColumn('products', 'taa_compliant')) {
                $table->boolean('taa_compliant')->default(false)
                    ->comment('Trade Agreements Act compliant');
            }
            if (! Schema::hasColumn('products', 'made_in_usa')) {
                $table->boolean('made_in_usa')->default(false);
            }
            if (! Schema::hasColumn('products', 'gsa_approved')) {
                $table->boolean('gsa_approved')->default(false)
                    ->comment('On GSA Schedule');
            }
            if (! Schema::hasColumn('products', 'qpl_listed')) {
                $table->boolean('qpl_listed')->default(false)
                    ->comment('Qualified Products List');
            }

            // Additional Product Information
            if (! Schema::hasColumn('products', 'material')) {
                $table->string('material', 100)->nullable();
            }
            if (! Schema::hasColumn('products', 'finish')) {
                $table->string('finish', 100)->nullable();
            }
            if (! Schema::hasColumn('products', 'specifications')) {
                $table->text('specifications')->nullable()
                    ->comment('Detailed technical specifications');
            }
            if (! Schema::hasColumn('products', 'datasheet_url')) {
                $table->string('datasheet_url', 500)->nullable();
            }
            if (! Schema::hasColumn('products', 'msds_url')) {
                $table->string('msds_url', 500)->nullable()
                    ->comment('Material Safety Data Sheet URL');
            }

            // Pricing Tiers
            if (! Schema::hasColumn('products', 'gsa_price')) {
                $table->decimal('gsa_price', 12, 2)->nullable()
                    ->comment('GSA Schedule pricing');
            }
            if (! Schema::hasColumn('products', 'contract_price')) {
                $table->decimal('contract_price', 12, 2)->nullable()
                    ->comment('Contract-specific pricing');
            }

            // Search optimization
            if (! Schema::hasColumn('products', 'search_keywords')) {
                $table->text('search_keywords')->nullable()
                    ->comment('Additional keywords for search');
            }
        });

        // Add indexes separately with safety checks
        $this->addIndexIfNotExists('products', 'mil_spec_number', 'products_mil_spec_number_index');
        $this->addIndexIfNotExists('products', 'nsn', 'products_nsn_index');
        $this->addIndexIfNotExists('products', 'standard_type', 'products_standard_type_index');
        $this->addIndexIfNotExists('products', 'naics_code', 'products_naics_code_index');
        $this->addIndexIfNotExists('products', 'cage_code', 'products_cage_code_index');
        $this->addIndexIfNotExists('products', ['gsa_approved', 'is_active'], 'products_gsa_approved_is_active_index');
        $this->addIndexIfNotExists('products', ['berry_compliant', 'taa_compliant', 'made_in_usa'], 'products_berry_compliant_taa_compliant_made_in_usa_index');
    }

    private function addIndexIfNotExists(string $table, string|array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                $t->index($columns);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index already exists, ignore the error
            if (! str_contains($e->getMessage(), 'Duplicate key name') &&
                ! str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
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

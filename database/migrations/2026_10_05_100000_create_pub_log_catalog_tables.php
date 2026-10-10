<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The catalog may live in its own database (nsn_lookup.connection).
    public function getConnection(): ?string
    {
        return config('nsn_lookup.connection') ?: null;
    }

    public function up(): void
    {
        Schema::create('pub_log_imports', function (Blueprint $table) {
            $table->id();
            $table->date('release_date');
            $table->string('fingerprint', 64)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->json('manifest');
            $table->json('progress')->nullable();
            $table->json('validation')->nullable();
            $table->text('error')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('pub_log_catalog_state', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->foreignId('active_import_id')->nullable()->constrained('pub_log_imports')->restrictOnDelete();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('check_status')->nullable();
            $table->json('remote_manifest')->nullable();
            $table->text('check_error')->nullable();
            $table->timestamps();
        });
        DB::table('pub_log_catalog_state')->insert(['id' => 1]);

        Schema::create('nsn_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('pub_log_imports')->restrictOnDelete();
            $table->string('niin', 9);
            $table->string('fsc', 4);
            $table->string('nsn', 13);
            $table->string('inc', 5);
            $table->text('item_name');
            $table->text('sos');
            $table->text('end_item_name');
            $table->text('cancelled_niin');
            $table->unsignedBigInteger('source_row');
            $table->string('row_hash', 64);
            $table->unique(['import_id', 'niin']);
            $table->index(['import_id', 'nsn']);
        });
        Schema::create('nsn_references', function (Blueprint $table) {
            // Case-sensitive original values and keys on MySQL as well as SQLite.
            if (DB::getDriverName() === 'mysql') {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_bin';
            }
            $table->id();
            $table->foreignId('import_id')->constrained('pub_log_imports')->restrictOnDelete();
            $table->string('niin', 9);
            $table->string('part_number', 255);
            $table->string('part_search_key', 255);
            $table->string('cage_code', 5);
            foreach (['cage_status', 'rncc', 'rnvc', 'dac', 'rnaac', 'rnfc', 'rnsc', 'rnjc', 'sadc', 'hcc', 'msds', 'medals'] as $field) {
                $table->string($field, 255);
            }
            $table->unsignedBigInteger('source_row');
            $table->binary('row_hash', length: 32, fixed: true);
            $table->unique(['import_id', 'row_hash']);
            $table->index(['import_id', 'niin', 'id']);
            $table->index(['import_id', 'part_search_key', 'id'], 'nsn_reference_part_search');
            $table->index(['import_id', 'cage_code', 'id']);
        });
        Schema::create('cage_entities', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_bin';
            }
            $table->id();
            $table->foreignId('import_id')->constrained('pub_log_imports')->restrictOnDelete();
            $table->string('cage_code', 5);
            $table->json('summary')->nullable();
            $table->json('address')->nullable();
            $table->unsignedBigInteger('summary_row')->nullable();
            $table->unsignedBigInteger('address_row')->nullable();
            $table->string('summary_hash', 64)->nullable();
            $table->string('address_hash', 64)->nullable();
            $table->unique(['import_id', 'cage_code']);
        });
        Schema::create('nsn_catalog_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('pub_log_imports')->restrictOnDelete();
            $table->string('kind', 20);
            $table->string('niin', 9);
            foreach (['related_nsn', 'isc', 'orig_stdzn_dec', 'dt_stdzn_dec', 'niin_stat_cd', 'cancelled_niin_fsc', 'cancelled_niin', 'eff_date', 'demil'] as $field) {
                $table->string($field, 255)->nullable();
            }
            $table->unsignedBigInteger('source_row');
            $table->binary('row_hash', length: 32, fixed: true);
            $table->unique(['import_id', 'kind', 'row_hash'], 'nsn_relation_identity');
            $table->index(['import_id', 'niin', 'kind'], 'nsn_relation_item');
        });
        Schema::create('pub_log_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('pub_log_imports')->restrictOnDelete();
            $table->string('code', 255);
            $table->text('literal');
            $table->text('description');
            $table->text('regs');
            $table->unsignedBigInteger('source_row');
            $table->string('row_hash', 64);
            $table->unique(['import_id', 'row_hash']);
            $table->index(['import_id', 'code']);
        });

    }

    public function down(): void
    {
        foreach (['pub_log_definitions', 'nsn_catalog_relationships', 'cage_entities', 'nsn_references', 'nsn_catalog_items', 'pub_log_catalog_state', 'pub_log_imports'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

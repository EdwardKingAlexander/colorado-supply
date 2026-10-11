<?php

namespace App\Services\PubLog;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use RuntimeException;

/**
 * One catalog copy at a time (ai/modules/nsn-sourcing-workflow, phase 5).
 *
 * A release is written into staging tables (`<table>_next`). Activation
 * swaps them in with a rename and drops the previous tables, which frees
 * their disk space immediately (deleting rows would not: InnoDB keeps the
 * file at its largest size). Readers always use the live table names.
 *
 * SQLite index names and MySQL foreign key names are database-wide and
 * survive renames, so staging tables name every index/key with the import
 * ID to avoid clashing with the live copy's names.
 */
class PubLogTables
{
    /** Per-release data tables. pub_log_imports and pub_log_catalog_state are not swapped. */
    public const DATA = ['nsn_catalog_items', 'nsn_references', 'cage_entities', 'nsn_catalog_relationships', 'pub_log_definitions'];

    public static function staging(string $table): string
    {
        self::assertData($table);

        return $table.'_next';
    }

    public static function stagingExists(): bool
    {
        $schema = PubLogDb::connection()->getSchemaBuilder();
        foreach (self::DATA as $table) {
            if (! $schema->hasTable(self::staging($table))) {
                return false;
            }
        }

        return true;
    }

    public static function anyStagingExists(): bool
    {
        $schema = PubLogDb::connection()->getSchemaBuilder();
        foreach (self::DATA as $table) {
            if ($schema->hasTable(self::staging($table))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fresh, empty staging tables owned by one import. Any leftovers from an
     * abandoned import are dropped first.
     */
    public static function createStaging(int $importId): void
    {
        self::dropStaging();
        $schema = PubLogDb::connection()->getSchemaBuilder();
        foreach (self::DATA as $table) {
            $schema->create(self::staging($table), self::definition($table, 'i'.$importId));
        }
    }

    public static function dropStaging(): void
    {
        $schema = PubLogDb::connection()->getSchemaBuilder();
        foreach (array_reverse(self::DATA) as $table) {
            $schema->dropIfExists(self::staging($table));
        }
    }

    /**
     * Staging becomes live; the previous live tables are dropped. On MySQL
     * the rename of all tables is one atomic statement.
     */
    public static function swap(): void
    {
        if (! self::stagingExists()) {
            throw new RuntimeException('Staged catalog tables are missing; re-run the import before activating.');
        }
        $connection = PubLogDb::connection();
        $schema = $connection->getSchemaBuilder();
        foreach (array_reverse(self::DATA) as $table) {
            $schema->dropIfExists($table.'_old');
        }
        if ($connection->getDriverName() === 'mysql') {
            $pairs = [];
            foreach (self::DATA as $table) {
                $pairs[] = "`{$table}` TO `{$table}_old`, `{$table}_next` TO `{$table}`";
            }
            $connection->statement('RENAME TABLE '.implode(', ', $pairs));
        } else {
            $connection->transaction(function () use ($schema) {
                foreach (self::DATA as $table) {
                    $schema->rename($table, $table.'_old');
                    $schema->rename($table.'_next', $table);
                }
            });
        }
        foreach (array_reverse(self::DATA) as $table) {
            $schema->dropIfExists($table.'_old');
        }
    }

    /**
     * Same columns as the catalog migration (2026_10_05_100000); index and
     * foreign key names carry $token so they are unique per release.
     */
    public static function definition(string $table, string $token): Closure
    {
        self::assertData($table);
        // Case-sensitive original values and keys on MySQL, as in the migration.
        $binary = function (Blueprint $t) {
            if (PubLogDb::connection()->getDriverName() === 'mysql') {
                $t->charset = 'utf8mb4';
                $t->collation = 'utf8mb4_bin';
            }
        };
        $importId = fn (Blueprint $t, string $abbr) => $t->foreignId('import_id')->constrained('pub_log_imports', indexName: "pl_{$token}_{$abbr}_import")->restrictOnDelete();

        return match ($table) {
            'nsn_catalog_items' => function (Blueprint $t) use ($token, $importId) {
                $t->id();
                $importId($t, 'item');
                $t->string('niin', 9);
                $t->string('fsc', 4);
                $t->string('nsn', 13);
                $t->string('inc', 5);
                $t->text('item_name');
                $t->text('sos');
                $t->text('end_item_name');
                $t->text('cancelled_niin');
                $t->unsignedBigInteger('source_row');
                $t->string('row_hash', 64);
                $t->unique(['import_id', 'niin'], "pl_{$token}_item_niin");
                $t->index(['import_id', 'nsn'], "pl_{$token}_item_nsn");
            },
            'nsn_references' => function (Blueprint $t) use ($token, $importId, $binary) {
                $binary($t);
                $t->id();
                $importId($t, 'ref');
                $t->string('niin', 9);
                $t->string('part_number', 255);
                $t->string('part_search_key', 255);
                $t->string('cage_code', 5);
                foreach (['cage_status', 'rncc', 'rnvc', 'dac', 'rnaac', 'rnfc', 'rnsc', 'rnjc', 'sadc', 'hcc', 'msds', 'medals'] as $field) {
                    $t->string($field, 255);
                }
                $t->unsignedBigInteger('source_row');
                $t->binary('row_hash', length: 32, fixed: true);
                $t->unique(['import_id', 'row_hash'], "pl_{$token}_ref_hash");
                $t->index(['import_id', 'niin', 'id'], "pl_{$token}_ref_niin");
                $t->index(['import_id', 'part_search_key', 'id'], "pl_{$token}_ref_part");
                $t->index(['import_id', 'cage_code', 'id'], "pl_{$token}_ref_cage");
            },
            'cage_entities' => function (Blueprint $t) use ($token, $importId, $binary) {
                $binary($t);
                $t->id();
                $importId($t, 'cage');
                $t->string('cage_code', 5);
                $t->json('summary')->nullable();
                $t->json('address')->nullable();
                $t->unsignedBigInteger('summary_row')->nullable();
                $t->unsignedBigInteger('address_row')->nullable();
                $t->string('summary_hash', 64)->nullable();
                $t->string('address_hash', 64)->nullable();
                $t->unique(['import_id', 'cage_code'], "pl_{$token}_cage_code");
            },
            'nsn_catalog_relationships' => function (Blueprint $t) use ($token, $importId) {
                $t->id();
                $importId($t, 'rel');
                $t->string('kind', 20);
                $t->string('niin', 9);
                foreach (['related_nsn', 'isc', 'orig_stdzn_dec', 'dt_stdzn_dec', 'niin_stat_cd', 'cancelled_niin_fsc', 'cancelled_niin', 'eff_date', 'demil'] as $field) {
                    $t->string($field, 255)->nullable();
                }
                $t->unsignedBigInteger('source_row');
                $t->binary('row_hash', length: 32, fixed: true);
                $t->unique(['import_id', 'kind', 'row_hash'], "pl_{$token}_rel_identity");
                $t->index(['import_id', 'niin', 'kind'], "pl_{$token}_rel_item");
            },
            'pub_log_definitions' => function (Blueprint $t) use ($token, $importId) {
                $t->id();
                $importId($t, 'def');
                $t->string('code', 255);
                $t->text('literal');
                $t->text('description');
                $t->text('regs');
                $t->unsignedBigInteger('source_row');
                $t->string('row_hash', 64);
                $t->unique(['import_id', 'row_hash'], "pl_{$token}_def_hash");
                $t->index(['import_id', 'code'], "pl_{$token}_def_code");
            },
        };
    }

    private static function assertData(string $table): void
    {
        if (! in_array($table, self::DATA, true)) {
            throw new RuntimeException("{$table} is not a per-release catalog table.");
        }
    }
}

<?php

namespace App\Services\PubLog;

use App\Models\PubLogImport;
use RuntimeException;

class PubLogCatalog
{
    public function activeId(): ?int
    {
        $id = PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)->value('active_import_id');

        return $id === null ? null : (int) $id;
    }

    /** Capture once per lookup; every subsequent query must use this immutable import ID. */
    public function snapshot(): ?PubLogImport
    {
        $id = $this->activeId();

        return $id ? PubLogImport::findOrFail($id) : null;
    }

    public function status(): array
    {
        $snapshot = $this->snapshot();
        $state = PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)->first();

        return [
            'active_import_id' => $snapshot?->id,
            'release_date' => $snapshot?->release_date->format('Y-m-d'),
            'stale' => $snapshot ? $snapshot->release_date->lt(now()->subDays(config('nsn_lookup.stale_after_days'))->startOfDay()) : null,
            'check_status' => $state?->check_status,
            'last_checked_at' => $state?->last_checked_at,
            'check_error' => $state?->check_error,
        ];
    }

    public function validate(PubLogImport $import): array
    {
        $progress = $import->progress;
        foreach (PubLogSchema::members() as $member => $schema) {
            if (! ($progress[$member]['done'] ?? false)) {
                throw new RuntimeException("Incomplete member {$member}.");
            }
        }
        $counts = [];
        foreach (['nsn_catalog_items', 'nsn_references', 'cage_entities', 'nsn_catalog_relationships', 'pub_log_definitions'] as $table) {
            $counts[$table] = PubLogDb::connection()->table($table)->where('import_id', $import->id)->count();
            if ($counts[$table] === 0) {
                throw new RuntimeException("Empty required catalog table {$table}.");
            }
        }
        foreach (['P_FLIS_NSN.CSV', 'P_CAGE.CSV', 'V_CAGE_ADDRESS.CSV', 'V_FLIS_PART.CSV', 'P_HELP.CSV'] as $member) {
            if ($progress[$member]['rows'] === 0) {
                throw new RuntimeException("Empty required source {$member}.");
            }
        }
        $expectedCounts = [
            'nsn_catalog_items' => $progress['P_FLIS_NSN.CSV']['stored'],
            'nsn_references' => $progress['V_FLIS_PART.CSV']['stored'],
            'pub_log_definitions' => $progress['P_HELP.CSV']['stored'],
            'nsn_catalog_relationships' => $progress['V_FLIS_STANDARDIZATION.CSV']['stored'] + $progress['V_FLIS_CANCELLED_NIIN.CSV']['stored'],
        ];
        foreach ($expectedCounts as $table => $expected) {
            if ($counts[$table] !== $expected) {
                throw new RuntimeException("Checkpoint count mismatch in {$table}.");
            }
        }
        foreach (['summary' => 'P_CAGE.CSV', 'address' => 'V_CAGE_ADDRESS.CSV'] as $column => $member) {
            if (PubLogDb::connection()->table('cage_entities')->where('import_id', $import->id)->whereNotNull($column)->count() !== $progress[$member]['stored']) {
                throw new RuntimeException("Checkpoint count mismatch in {$member}.");
            }
        }
        $orphans = PubLogDb::connection()->table('nsn_references as r')
            ->where('r.import_id', $import->id)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('nsn_catalog_items as i')->whereColumn('i.import_id', 'r.import_id')->whereColumn('i.niin', 'r.niin'))
            ->count();
        $unresolved = PubLogDb::connection()->table('nsn_references as r')
            ->where('r.import_id', $import->id)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('cage_entities as c')->whereColumn('c.import_id', 'r.import_id')->whereColumn('c.cage_code', 'r.cage_code')->whereNotNull('c.summary'))
            ->count();
        $shortItemCodes = PubLogDb::connection()->table('nsn_catalog_items')->where('import_id', $import->id)
            ->where('inc', '<>', '')->whereRaw('LENGTH(inc) < 5')->count();
        $result = ['counts' => $counts, 'orphan_references' => $orphans, 'unresolved_cage_references' => $unresolved, 'short_item_name_codes' => $shortItemCodes];
        $result['invalid_cage_source_rows'] = [];
        foreach (['P_CAGE.CSV', 'V_CAGE_ADDRESS.CSV', 'V_FLIS_PART.CSV'] as $member) {
            $result['invalid_cage_source_rows'][$member] = $progress[$member]['invalid_cage_codes'] ?? 0;
        }
        $import->update(['validation' => $result]);
        if ($orphans > config('nsn_lookup.max_orphan_references')) {
            throw new RuntimeException("{$orphans} references have no matching NIIN; review the release before activation.");
        }
        $baselineId = $import->manifest['baseline_import_id'];
        if ($baselineId && ($baseline = PubLogImport::find($baselineId))) {
            foreach ($counts as $table => $count) {
                $previous = $baseline->validation['counts'][$table] ?? 0;
                if ($previous > 0 && $count < $previous * config('nsn_lookup.minimum_previous_count_ratio')) {
                    throw new RuntimeException("Unexpected catalog shrink in {$table}; the previous release remains active.");
                }
            }
        }

        return $result;
    }

    /** Compare-and-swap prevents late imports from replacing an intervening activation. */
    public function activate(int $importId, ?int $expectedCurrent, bool $rollback = false): void
    {
        PubLogDb::connection()->transaction(function () use ($importId, $expectedCurrent, $rollback) {
            $state = PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)->lockForUpdate()->first();
            $actual = $state->active_import_id === null ? null : (int) $state->active_import_id;
            if ($actual !== $expectedCurrent) {
                throw new RuntimeException('Active catalog changed; inspect status before retrying activation.');
            }
            $import = PubLogImport::whereKey($importId)->lockForUpdate()->firstOrFail();
            if ($import->status !== 'ready' || ! $import->completed_at || empty($import->validation['counts'])) {
                throw new RuntimeException('Only fully validated imports can be activated.');
            }
            if (! $rollback && $actual && $import->release_date->lt(PubLogImport::findOrFail($actual)->release_date)) {
                throw new RuntimeException('Use explicit rollback to activate an older release.');
            }
            $changed = PubLogDb::connection()->table('pub_log_catalog_state')->where('id', 1)
                ->where('active_import_id', $expectedCurrent)
                ->update(['active_import_id' => $importId, 'updated_at' => now()]);
            if ($changed !== 1 && $actual !== $importId) {
                throw new RuntimeException('Concurrent catalog activation; retry after checking status.');
            }
            $import->update(['activated_at' => now()]);
        });
    }
}

<?php

namespace App\Services\PubLog;

use App\Models\PubLogImport;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class PubLogImporter
{
    public function __construct(
        private PubLogArchives $archives,
        private PubLogCsv $csv,
        private PubLogWriter $writer,
        private PubLogCatalog $catalog,
    ) {}

    public function prepare(string $directory, string $releaseDate, bool $reimport = false): PubLogImport
    {
        return Cache::lock('publog:writer', 86400)->get(function () use ($directory, $releaseDate, $reimport) {
            $import = $this->archives->prepare($directory, $releaseDate);
            // The same files were imported before but that copy is gone (replaced or
            // abandoned), or --reimport rebuilds the live release: retire the old
            // fingerprint so the release can be imported again.
            if (in_array($import->status, ['replaced', 'abandoned'], true) || ($reimport && $import->activated_at)) {
                $import->update(['fingerprint' => hash('sha256', $import->fingerprint.'#'.$import->id)]);
                $import = $this->archives->prepare($directory, $releaseDate);
            }

            return $import;
        }) ?: throw new RuntimeException('Another PUB LOG import is running.');
    }

    /**
     * One import owns the staging tables at a time. Starting or resuming an
     * import abandons any other staged one; an import whose staging was lost
     * restarts from the beginning.
     */
    private function ensureStaging(PubLogImport $import): void
    {
        if ($import->activated_at) {
            return;
        }
        foreach (PubLogImport::whereKeyNot($import->id)->whereNull('activated_at')->whereNotIn('status', ['abandoned', 'replaced'])->get() as $other) {
            if ($other->manifest['staged'] ?? false) {
                $other->update(['status' => 'abandoned', 'manifest' => [...$other->manifest, 'staged' => false]]);
                $this->archives->removeOwnedDirectory($other);
            }
        }
        if (($import->manifest['staged'] ?? false) && PubLogTables::stagingExists()) {
            return;
        }
        PubLogTables::createStaging($import->id);
        $import->update(['status' => 'pending', 'progress' => [], 'validation' => [], 'completed_at' => null, 'manifest' => [...$import->manifest, 'staged' => true]]);
    }

    /**
     * CLI work is checkpointed, not sent to a normal 90-second queue reservation.
     * maxSeconds yields between batches; zero runs up to 23h, below the lock lease.
     */
    public function run(PubLogImport $import, int $maxSeconds = 0, ?callable $report = null): PubLogImport
    {
        $lock = Cache::lock('publog:writer', 86400);
        if (! $lock->get()) {
            throw new RuntimeException('Another PUB LOG import is running.');
        }
        $start = microtime(true);
        $budget = $maxSeconds > 0 ? min($maxSeconds, 82800) : 82800;
        try {
            $import->refresh();
            if (in_array($import->status, ['replaced', 'abandoned'], true)) {
                throw new RuntimeException('This import was '.$import->status.'; start a new import from the release files.');
            }
            if ($import->status === 'ready' && ($import->activated_at || (($import->manifest['staged'] ?? false) && PubLogTables::stagingExists()))) {
                return $import;
            }
            $this->archives->verify($import);
            PubLogDb::prepareForBulkWrite();
            $this->ensureStaging($import);
            $import->refresh();
            $import->update(['status' => 'importing', 'error' => null]);
            $progress = $import->progress ?? [];
            foreach (PubLogSchema::members() as $member => $schema) {
                if ($progress[$member]['done'] ?? false) {
                    $this->archives->removeSpool($import, $member);

                    continue;
                }
                $path = $this->archives->spool($import, $member);
                $stream = fopen($path, 'rb');
                if (! is_resource($stream)) {
                    throw new RuntimeException("Cannot read {$member}.");
                }
                try {
                    $header = $this->csv->header($stream, $schema['fields']);
                    $checkpoint = $progress[$member] ?? ['offset' => ftell($stream), 'rows' => 0, 'stored' => 0, 'done' => false];
                    if (fseek($stream, $checkpoint['offset']) !== 0) {
                        throw new RuntimeException('Cannot seek import checkpoint.');
                    }
                    $batchSize = max(1, min(500, (int) config('nsn_lookup.batch_size')));
                    while (! $checkpoint['done']) {
                        if (microtime(true) - $start >= $budget) {
                            $report && $report($member, $checkpoint, 'paused');

                            return $import->refresh();
                        }
                        $batch = [];
                        for ($i = 0; $i < $batchSize; $i++) {
                            $values = $this->csv->read($stream);
                            if ($values === false) {
                                $checkpoint['done'] = true;
                                break;
                            }
                            $checkpoint['rows']++;
                            $row = $this->csv->row($values, $header, $schema['encoding']);
                            if (isset($row['CAGE_CODE']) && preg_match('/^[A-Z0-9]{5}$/D', $row['CAGE_CODE']) !== 1) {
                                $checkpoint['invalid_cage_codes'] = ($checkpoint['invalid_cage_codes'] ?? 0) + 1;
                            }
                            $batch[] = [$checkpoint['rows'], $row];
                        }
                        $checkpoint['offset'] = ftell($stream);
                        $this->archives->assertSpace(dirname($path));
                        PubLogDb::connection()->transaction(function () use ($import, $member, $batch, &$checkpoint, &$progress) {
                            $checkpoint['stored'] += $this->writer->write($import->id, $member, $batch);
                            $progress[$member] = $checkpoint;
                            $import->update(['progress' => $progress]);
                        });
                        $report && $report($member, $checkpoint, 'importing');
                    }
                } finally {
                    fclose($stream);
                }
                // Only a generated spool for this verified member; original ZIPs are retained.
                $this->archives->removeSpool($import, $member);
            }
            $import->update(['status' => 'validating']);
            $validation = $this->catalog->validate($import->refresh());
            $import->update(['status' => 'ready', 'validation' => $validation, 'completed_at' => now()]);
            $report && $report('catalog', $validation, 'ready');

            return $import->refresh();
        } catch (Throwable $exception) {
            $import->refresh()->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 4000)]);
            throw $exception;
        } finally {
            $lock->release();
        }
    }
}

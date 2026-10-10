<?php

namespace App\Services\PubLog;

use App\Models\PubLogImport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class PubLogArchives
{
    public function prepare(string $directory, string $releaseDate): PubLogImport
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $releaseDate);
        if (! $date || $date->format('Y-m-d') !== $releaseDate || $date->isFuture()) {
            throw new RuntimeException('Use a valid, non-future release date in YYYY-MM-DD format.');
        }

        $archives = [];
        $total = 0;
        $timestamps = [];
        foreach (['IDENTIFICATION', 'CAGE', 'REFERENCE'] as $name) {
            $path = realpath($directory.DIRECTORY_SEPARATOR.$name.'.zip');
            if (! $path || ! is_file($path) || filesize($path) > config('nsn_lookup.max_archive_bytes')) {
                throw new RuntimeException("Missing or oversized {$name}.zip.");
            }
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
                throw new RuntimeException("Invalid {$name}.zip.");
            }
            try {
                $entries = [];
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->statIndex($i);
                    $member = $entry['name'];
                    $allowed = array_merge(array_keys(PubLogSchema::members()), PubLogSchema::optionalMembers());
                    if (! in_array($member, $allowed, true) || isset($entries[$member]) || ($entry['encryption_method'] ?? 0) !== 0) {
                        throw new RuntimeException("Unexpected, duplicate or encrypted archive member: {$member}.");
                    }
                    if (isset(PubLogSchema::members()[$member]) && PubLogSchema::members()[$member]['archive'] !== $name) {
                        throw new RuntimeException("Member {$member} is in the wrong archive.");
                    }
                    if ($entry['size'] > config('nsn_lookup.max_member_bytes') || $entry['size'] < 1) {
                        throw new RuntimeException("Invalid declared size for {$member}.");
                    }
                    $total += $entry['size'];
                    $timestamps[] = $entry['mtime'];
                    $entries[$member] = ['bytes' => $entry['size'], 'crc' => sprintf('%08x', $entry['crc']), 'modified_at' => $entry['mtime']];
                }
                foreach (PubLogSchema::members() as $member => $schema) {
                    if ($schema['archive'] === $name && ! isset($entries[$member])) {
                        throw new RuntimeException("Missing required member {$member}.");
                    }
                }
                $archives[$name] = ['source_path' => $path, 'bytes' => filesize($path), 'sha256' => hash_file('sha256', $path), 'members' => $entries];
            } finally {
                $zip->close();
            }
        }
        if ($total > config('nsn_lookup.max_total_bytes')) {
            throw new RuntimeException('Archive set exceeds the expanded size limit.');
        }
        if (max($timestamps) - min($timestamps) > config('nsn_lookup.max_release_spread_days') * 86400
            || max($timestamps) > $date->endOfDay()->timestamp
            || min($timestamps) < $date->subDays(35)->timestamp) {
            throw new RuntimeException('Archive timestamps do not describe a coherent release. Check all three downloads.');
        }

        $fingerprint = hash('sha256', $releaseDate.json_encode(array_column($archives, 'sha256'), JSON_THROW_ON_ERROR));
        if ($existing = PubLogImport::where('fingerprint', $fingerprint)->first()) {
            return $existing;
        }
        $root = config('nsn_lookup.directory');
        File::ensureDirectoryExists($root);
        // Conservative local staging allowance; target database capacity is also an operator deployment gate.
        $required = $total * 3 + max(array_column(array_merge(...array_column($archives, 'members')), 'bytes')) + array_sum(array_column($archives, 'bytes'));
        $this->assertSpace($root, $required);
        $ownedDirectory = $root.DIRECTORY_SEPARATOR.(string) Str::uuid();
        File::ensureDirectoryExists($ownedDirectory);
        foreach ($archives as $name => &$archive) {
            $destination = $ownedDirectory.DIRECTORY_SEPARATOR.$name.'.zip';
            if (! copy($archive['source_path'], $destination) || hash_file('sha256', $destination) !== $archive['sha256']) {
                throw new RuntimeException("Copy/checksum failed for {$name}.zip; no catalog was activated.");
            }
            unset($archive['source_path']);
            $archive['path'] = $destination;
        }
        unset($archive);

        return PubLogImport::create([
            'release_date' => $releaseDate, 'fingerprint' => $fingerprint, 'status' => 'pending',
            'manifest' => ['directory' => $ownedDirectory, 'archives' => $archives, 'schema' => 'publog-2026-10', 'baseline_import_id' => app(PubLogCatalog::class)->activeId()],
            'progress' => [], 'validation' => [],
        ]);
    }

    public function verify(PubLogImport $import): void
    {
        foreach ($import->manifest['archives'] as $archive) {
            if (! is_file($archive['path']) || hash_file('sha256', $archive['path']) !== $archive['sha256']) {
                throw new RuntimeException('An import archive was removed or changed; restore the original file before resuming.');
            }
        }
    }

    /** Spool only the current member to permit O(1) checkpoint seeks on restart. */
    public function spool(PubLogImport $import, string $member): string
    {
        $schema = PubLogSchema::members()[$member];
        $archive = $import->manifest['archives'][$schema['archive']];
        $expected = $archive['members'][$member];
        $path = $import->manifest['directory'].DIRECTORY_SEPARATOR.$member.'.spool';
        if (is_file($path) && filesize($path) === $expected['bytes'] && hash_file('crc32b', $path) === $expected['crc']) {
            return $path;
        }
        $this->assertSpace(dirname($path), $expected['bytes']);
        $zip = new ZipArchive;
        if ($zip->open($archive['path']) !== true) {
            throw new RuntimeException('Cannot open verified archive.');
        }
        try {
            $source = $zip->getStream($member);
            $destination = fopen($path, 'wb');
            if (! is_resource($source) || ! is_resource($destination)) {
                throw new RuntimeException("Cannot open stream for {$member}.");
            }
            try {
                $copied = stream_copy_to_stream($source, $destination, $expected['bytes'] + 1);
            } finally {
                fclose($source);
                fclose($destination);
            }
            if ($copied !== $expected['bytes'] || hash_file('crc32b', $path) !== $expected['crc']) {
                throw new RuntimeException("CRC/length mismatch in {$member}.");
            }
        } finally {
            $zip->close();
        }

        return $path;
    }

    public function removeSpool(PubLogImport $import, string $member): void
    {
        if (! isset(PubLogSchema::members()[$member])) {
            throw new RuntimeException('Unknown PUB LOG member.');
        }
        $path = $import->manifest['directory'].DIRECTORY_SEPARATOR.$member.'.spool';
        if (is_file($path) && ! unlink($path)) {
            throw new RuntimeException('Cannot remove completed member spool.');
        }
    }

    public function assertSpace(string $path, int $additional = 0): void
    {
        $free = disk_free_space($path);
        if ($free === false || $free < $additional + config('nsn_lookup.minimum_free_bytes')) {
            throw new RuntimeException('Insufficient staging disk space; the active catalog has not changed.');
        }
    }
}

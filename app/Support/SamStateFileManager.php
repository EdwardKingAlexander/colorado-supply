<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Persist query state to JSON files for scheduled tasks and debugging.
 *
 * This service:
 * - Saves/loads state files to storage/app/sam/state/
 * - Tracks last successful fetch timestamp
 * - Tracks last used parameters
 * - Tracks last results summary
 * - Optional rotation (keep last N state files)
 */
class SamStateFileManager
{
    /**
     * Directory for state files within storage/app.
     */
    protected const STATE_DIRECTORY = 'sam/state';

    /**
     * Default number of state files to keep.
     */
    protected const DEFAULT_KEEP_COUNT = 10;

    /**
     * Storage disk to use.
     */
    protected string $disk;

    /**
     * Create a new state file manager instance.
     */
    public function __construct(?string $disk = null)
    {
        $this->disk = $disk ?? 'local';
    }

    /**
     * Save current state to a timestamped JSON file.
     *
     * @param  array  $params  Query parameters used
     * @param  array  $summary  Results summary
     * @param  array  $failedNaics  Failed NAICS details (optional)
     * @return string Path to saved state file
     */
    public function save(array $params, array $summary, array $failedNaics = []): string
    {
        $this->ensureDirectoryExists();

        $timestamp = now();
        $filename = $this->generateFilename($timestamp);

        $state = [
            'timestamp' => $timestamp->toIso8601String(),
            'params' => $params,
            'summary' => $summary,
            'failed_naics' => $failedNaics,
        ];

        $path = $this->getStatePath($filename);

        Storage::disk($this->disk)->put($path, json_encode($state, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Load the most recent state file.
     *
     * @return array|null State data or null if no state files exist
     */
    public function loadLatest(): ?array
    {
        $files = $this->getStateFiles();

        if (empty($files)) {
            return null;
        }

        $latestFile = $files[0]; // Already sorted by modified time (newest first)

        return $this->loadFile($latestFile);
    }

    /**
     * Load state file by filename.
     *
     * @param  string  $filename  State filename
     * @return array|null State data or null if file doesn't exist
     */
    public function load(string $filename): ?array
    {
        $path = $this->getStatePath($filename);

        if (! Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        return $this->loadFile($path);
    }

    /**
     * Load state file from path.
     *
     * @param  string  $path  Full path to state file
     * @return array State data
     */
    protected function loadFile(string $path): array
    {
        $contents = Storage::disk($this->disk)->get($path);
        $state = json_decode($contents, true);

        return $state ?? [];
    }

    /**
     * Get all state files sorted by modified time (newest first).
     *
     * @return array Array of file paths
     */
    public function getStateFiles(): array
    {
        $this->ensureDirectoryExists();

        $files = Storage::disk($this->disk)->files(self::STATE_DIRECTORY);

        // Filter to only .json files
        $files = array_filter($files, fn ($file) => str_ends_with($file, '.json'));

        // Sort by last modified time (newest first)
        usort($files, function ($a, $b) {
            return Storage::disk($this->disk)->lastModified($b) <=> Storage::disk($this->disk)->lastModified($a);
        });

        return array_values($files);
    }

    /**
     * Rotate state files, keeping only the N most recent files.
     *
     * @param  int  $keepCount  Number of files to keep
     * @return int Number of files deleted
     */
    public function rotate(int $keepCount = self::DEFAULT_KEEP_COUNT): int
    {
        $files = $this->getStateFiles();

        if (count($files) <= $keepCount) {
            return 0;
        }

        $filesToDelete = array_slice($files, $keepCount);
        $deletedCount = 0;

        foreach ($filesToDelete as $file) {
            if (Storage::disk($this->disk)->delete($file)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Delete all state files.
     *
     * @return int Number of files deleted
     */
    public function clear(): int
    {
        $files = $this->getStateFiles();
        $deletedCount = 0;

        foreach ($files as $file) {
            if (Storage::disk($this->disk)->delete($file)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get summary from latest state file.
     *
     * @return array|null Summary data or null if no state exists
     */
    public function getLatestSummary(): ?array
    {
        $state = $this->loadLatest();

        return $state['summary'] ?? null;
    }

    /**
     * Get timestamp from latest state file.
     *
     * @return string|null ISO 8601 timestamp or null if no state exists
     */
    public function getLatestTimestamp(): ?string
    {
        $state = $this->loadLatest();

        return $state['timestamp'] ?? null;
    }

    /**
     * Get failed NAICS from latest state file.
     *
     * @return array Failed NAICS details (empty array if none)
     */
    public function getLatestFailedNaics(): array
    {
        $state = $this->loadLatest();

        return $state['failed_naics'] ?? [];
    }

    /**
     * Check if state directory has any files.
     *
     * @return bool True if state files exist
     */
    public function hasState(): bool
    {
        return count($this->getStateFiles()) > 0;
    }

    /**
     * Get count of state files.
     *
     * @return int Number of state files
     */
    public function count(): int
    {
        return count($this->getStateFiles());
    }

    /**
     * Ensure state directory exists.
     */
    protected function ensureDirectoryExists(): void
    {
        if (! Storage::disk($this->disk)->exists(self::STATE_DIRECTORY)) {
            Storage::disk($this->disk)->makeDirectory(self::STATE_DIRECTORY);
        }
    }

    /**
     * Generate filename for state file.
     *
     * @param  \Carbon\Carbon  $timestamp  Timestamp for filename
     * @return string Filename
     */
    protected function generateFilename($timestamp): string
    {
        return 'sam_state_'.$timestamp->format('Y-m-d_H-i-s-u').'.json';
    }

    /**
     * Get full path for state file.
     *
     * @param  string  $filename  State filename
     * @return string Full path
     */
    protected function getStatePath(string $filename): string
    {
        // Remove directory prefix if already included
        $filename = str_replace(self::STATE_DIRECTORY.'/', '', $filename);

        return self::STATE_DIRECTORY.'/'.$filename;
    }

    /**
     * Get all state data with metadata.
     *
     * @return array Array of state data with file metadata
     */
    public function all(): array
    {
        $files = $this->getStateFiles();
        $states = [];

        foreach ($files as $file) {
            $state = $this->loadFile($file);
            $state['_file'] = basename($file);
            $state['_modified'] = Storage::disk($this->disk)->lastModified($file);
            $state['_size'] = Storage::disk($this->disk)->size($file);

            $states[] = $state;
        }

        return $states;
    }

    /**
     * Persist legacy-format state to the shared UI/export path.
     *
     * @return string|null Full path written, or null on failure
     */
    public function saveLegacy(array $legacy): ?string
    {
        $path = app_path('Mcp/Servers/Business/State/sam-opportunities.json');

        try {
            $dir = dirname($path);
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            File::put($path, json_encode($legacy, JSON_PRETTY_PRINT));

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Failed to persist legacy SAM opportunities state', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return null;
        }
    }
}

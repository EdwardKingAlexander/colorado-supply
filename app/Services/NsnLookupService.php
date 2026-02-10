<?php

namespace App\Services;

use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class NsnLookupService
{
    /**
     * Path to the Python script.
     */
    protected string $scriptPath;

    /**
     * Path to Python executable (can be configured).
     */
    protected string $pythonPath;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/nsn_lookup.py');
        $this->pythonPath = $this->resolvePythonPath();
    }

    /**
     * Resolve the Python executable path, preferring the virtual environment.
     */
    protected function resolvePythonPath(): string
    {
        // Check for explicit config/env setting first
        $configPath = config('services.python.path');
        if ($configPath && $configPath !== 'python' && file_exists($configPath)) {
            return $configPath;
        }

        // Check for virtual environment in project root
        $venvPaths = [
            base_path('.venv/Scripts/python.exe'),  // Windows
            base_path('.venv/bin/python'),          // Linux/Mac
            base_path('venv/Scripts/python.exe'),   // Windows alt
            base_path('venv/bin/python'),           // Linux/Mac alt
        ];

        foreach ($venvPaths as $venvPath) {
            if (file_exists($venvPath)) {
                return $venvPath;
            }
        }

        // Fall back to system python
        return 'python';
    }

    /**
     * Fetch NSN data using crawl4ai Python script and persist it.
     */
    public function fetchAndPersistNsnData(string $nsn): ?MilSpecPart
    {
        $normalizedNsn = $this->normalizeNsn($nsn);

        if (! $normalizedNsn) {
            Log::warning("Invalid NSN format provided: {$nsn}");

            return null;
        }

        Log::info("Fetching NSN data via crawl4ai: {$normalizedNsn}");

        $data = $this->fetchViaPython($normalizedNsn);

        if (! $data || ! ($data['success'] ?? false)) {
            Log::warning('NSN fetch failed', [
                'nsn' => $normalizedNsn,
                'error' => $data['error'] ?? 'Unknown error',
            ]);

            return null;
        }

        return $this->persistNsnData($data);
    }

    /**
     * Call the Python script to fetch NSN data.
     */
    protected function fetchViaPython(string $nsn): ?array
    {
        if (! file_exists($this->scriptPath)) {
            Log::error('NSN lookup script not found', ['path' => $this->scriptPath]);

            return [
                'success' => false,
                'error' => 'Python script not found. Run: pip install -r scripts/requirements.txt && crawl4ai-setup',
            ];
        }

        try {
            $result = Process::timeout(60)->run([
                $this->pythonPath,
                $this->scriptPath,
                $nsn,
            ]);

            if (! $result->successful()) {
                Log::error('Python script failed', [
                    'nsn' => $nsn,
                    'exit_code' => $result->exitCode(),
                    'stderr' => $result->errorOutput(),
                ]);

                // Try to parse error from stdout anyway
                $output = trim($result->output());
                if ($output && str_starts_with($output, '{')) {
                    return json_decode($output, true);
                }

                return [
                    'success' => false,
                    'error' => $result->errorOutput() ?: 'Script execution failed',
                ];
            }

            $output = trim($result->output());

            if (empty($output)) {
                return [
                    'success' => false,
                    'error' => 'Empty response from script',
                ];
            }

            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse script output', [
                    'nsn' => $nsn,
                    'output' => substr($output, 0, 500),
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid JSON from script',
                ];
            }

            Log::info('NSN data fetched successfully', [
                'nsn' => $nsn,
                'has_description' => ! empty($data['description']),
                'has_manufacturer' => ! empty($data['manufacturer_name']),
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('NSN fetch exception', [
                'nsn' => $nsn,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Persists the fetched NSN data into the database.
     */
    protected function persistNsnData(array $data): MilSpecPart
    {
        // Handle manufacturer
        $manufacturerName = trim((string) ($data['manufacturer_name'] ?? ''));
        $manufacturerName = $manufacturerName !== '' ? $manufacturerName : 'Unknown Manufacturer';
        $manufacturerCage = $this->normalizeCageCode($data['manufacturer_cage'] ?? null);

        $manufacturerLookup = $manufacturerCage
            ? ['cage_code' => $manufacturerCage]
            : ['name' => $manufacturerName];

        $manufacturer = Manufacturer::updateOrCreate(
            $manufacturerLookup,
            [
                'name' => $manufacturerName,
                'cage_code' => $manufacturerCage,
            ]
        );

        // Create or update the MilSpecPart
        $milSpecPart = MilSpecPart::updateOrCreate(
            ['nsn' => $data['nsn']],
            [
                'description' => $data['description'] ?? "NSN {$data['nsn']}",
                'manufacturer_part_number' => $data['manufacturer_part_number'] ?? null,
                'manufacturer_id' => $manufacturer->id,
            ]
        );

        Log::info('NSN data persisted', [
            'nsn' => $data['nsn'],
            'mil_spec_part_id' => $milSpecPart->id,
            'manufacturer_id' => $manufacturer->id,
        ]);

        return $milSpecPart;
    }

    /**
     * Normalize NSN to XXXX-XX-XXX-XXXX format.
     */
    protected function normalizeNsn(?string $nsn): ?string
    {
        if (! $nsn) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $nsn);

        if (strlen($digits) !== 13) {
            return null;
        }

        return sprintf(
            '%s-%s-%s-%s',
            substr($digits, 0, 4),
            substr($digits, 4, 2),
            substr($digits, 6, 3),
            substr($digits, 9, 4)
        );
    }

    /**
     * Normalize CAGE code to 5-character alphanumeric format.
     */
    protected function normalizeCageCode(?string $cageCode): ?string
    {
        if (! $cageCode) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $cageCode));

        return preg_match('/^[A-Z0-9]{5}$/', $normalized) ? $normalized : null;
    }
}

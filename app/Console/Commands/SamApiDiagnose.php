<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-command diagnosis of SAM.gov API connectivity.
 *
 * Diagnosing the 2026-07-09 outage took hours of manual probing: separating a
 * dead endpoint from a bad key, from a local network fault, from an empty
 * result set. This command runs those probes in sequence and prints a verdict.
 *
 * Opt-in only — it makes real network calls, so it is never run by the test
 * suite or the scheduler.
 */
class SamApiDiagnose extends Command
{
    protected $signature = 'sam:diagnose {--json : Output machine-readable JSON for monitoring}';

    protected $description = 'Diagnose SAM.gov API connectivity and credentials';

    public function handle(): int
    {
        $baseUrl = (string) config('services.sam.base_url');
        $apiKey = (string) config('services.sam.api_key');

        $report = [
            'endpoint' => $baseUrl,
            'api_key_present' => $apiKey !== '',
            'api_key_length' => strlen($apiKey),
            'api_key_shape' => $this->describeKeyShape($apiKey),
            'probes' => [],
        ];

        $window = [
            'postedFrom' => now()->subDays(7)->format('m/d/Y'),
            'postedTo' => now()->format('m/d/Y'),
            'limit' => 1,
            'offset' => 0,
        ];

        // 1. The real query, as the application makes it.
        $report['probes']['with_key'] = $this->probe($baseUrl, $window + ['api_key' => $apiKey]);

        // 2. Same query without a key. Distinguishes "credential rejected"
        //    (route alive) from "route missing" (same answer either way).
        $report['probes']['without_key'] = $this->probe($baseUrl, $window);

        // 3. Control: is SAM.gov reachable at all from this host?
        $report['probes']['sam_gov_control'] = $this->probe('https://sam.gov', []);

        $report['verdict'] = $this->verdict($report);
        $healthy = $report['verdict']['healthy'];

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->renderReport($report);

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Issue one probe request and capture its diagnostic surface.
     *
     * @return array<string, mixed>
     */
    protected function probe(string $url, array $query): array
    {
        try {
            $response = Http::timeout((int) (config('services.sam.timeout') ?: 30))
                ->withHeaders(['Accept' => 'application/json'])
                ->get($url, $query);

            $body = $response->body();
            $decoded = json_decode($body, true);

            return [
                'ok' => true,
                'status' => $response->status(),
                'body_length' => strlen($body),
                'server' => $response->header('Server') ?: null,
                'envoy_upstream_ms' => $response->header('x-envoy-upstream-service-time') ?: null,
                'total_records' => is_array($decoded) ? ($decoded['totalRecords'] ?? null) : null,
                'json_keys' => is_array($decoded) ? array_slice(array_keys($decoded), 0, 8) : null,
                'body_excerpt' => $body === '' ? '' : substr(preg_replace('/\s+/', ' ', $body) ?? '', 0, 160),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => null,
                'error' => $e->getMessage(),
                'exception' => class_basename($e),
            ];
        }
    }

    /**
     * Interpret the probes.
     *
     * @return array{healthy: bool, summary: string, detail: string}
     */
    protected function verdict(array $report): array
    {
        $withKey = $report['probes']['with_key'];
        $withoutKey = $report['probes']['without_key'];
        $control = $report['probes']['sam_gov_control'];

        if (! $report['api_key_present']) {
            return [
                'healthy' => false,
                'summary' => 'No API key configured',
                'detail' => 'Set SAM_API_KEY in .env.',
            ];
        }

        if (! $withKey['ok']) {
            return [
                'healthy' => false,
                'summary' => 'Request threw before receiving a response',
                'detail' => ($withKey['exception'] ?? 'Error').': '.($withKey['error'] ?? 'unknown'),
            ];
        }

        $status = $withKey['status'];

        if ($status === 200) {
            return [
                'healthy' => true,
                'summary' => 'Endpoint healthy',
                'detail' => 'HTTP 200, totalRecords='.($withKey['total_records'] ?? 'n/a').'.',
            ];
        }

        // The signature of the 2026-07-09 gateway outage.
        if ($status === 404 && $withKey['body_length'] === 0) {
            $controlUp = ($control['ok'] ?? false) && ($control['status'] ?? 0) < 400;

            return [
                'healthy' => false,
                'summary' => 'Endpoint unreachable (empty-bodied 404)',
                'detail' => $controlUp
                    ? 'sam.gov itself responds, so this is not a local network fault. The endpoint has moved or its gateway is misrouting. Try setting SAM_API_BASE_URL to an alternate host.'
                    : 'sam.gov is also unreachable — check this host\'s network connectivity first.',
            ];
        }

        if ($status === 404) {
            return [
                'healthy' => true,
                'summary' => 'No matching records',
                'detail' => 'SAM.gov documents 404 with a body as "no data found". The endpoint is alive.',
            ];
        }

        if (in_array($status, [401, 403], true)) {
            return [
                'healthy' => false,
                'summary' => 'Credential rejected (HTTP '.$status.')',
                'detail' => 'The route is alive but the API key was refused. Check or rotate SAM_API_KEY.',
            ];
        }

        $keylessDiffers = ($withoutKey['status'] ?? null) !== $status;

        return [
            'healthy' => false,
            'summary' => 'Unexpected HTTP '.$status,
            'detail' => $keylessDiffers
                ? 'A keyless request returned HTTP '.($withoutKey['status'] ?? 'n/a').', so the route exists and the failure is request-specific.'
                : 'A keyless request returned the same status, suggesting the route itself is the problem rather than the credential.',
        ];
    }

    protected function renderReport(array $report): void
    {
        $this->newLine();
        $this->line('<options=bold>SAM.gov API diagnosis</>');
        $this->newLine();

        $this->table(['Setting', 'Value'], [
            ['Endpoint', $report['endpoint']],
            ['API key present', $report['api_key_present'] ? 'yes' : 'NO'],
            ['API key length', (string) $report['api_key_length']],
            ['API key shape', $report['api_key_shape']],
        ]);

        $rows = [];

        foreach ($report['probes'] as $name => $p) {
            $rows[] = [
                $name,
                $p['ok'] ? (string) $p['status'] : 'ERROR',
                $p['ok'] ? (string) $p['body_length'] : ($p['exception'] ?? ''),
                $p['server'] ?? '-',
                $p['ok'] ? (string) ($p['total_records'] ?? '-') : '-',
            ];
        }

        $this->table(['Probe', 'HTTP', 'Body bytes', 'Server', 'totalRecords'], $rows);

        $verdict = $report['verdict'];

        if ($verdict['healthy']) {
            $this->info('✓ '.$verdict['summary']);
        } else {
            $this->error('✗ '.$verdict['summary']);
        }

        $this->line('  '.$verdict['detail']);
        $this->newLine();
    }

    /**
     * Describe the key's format without ever revealing it.
     */
    protected function describeKeyShape(string $key): string
    {
        if ($key === '') {
            return 'missing';
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key)) {
            return 'UUID (SAM.gov system account style)';
        }

        if (preg_match('/^[A-Za-z0-9]{40}$/', $key)) {
            return '40-char alphanumeric (api.data.gov public key style)';
        }

        return 'unrecognised ('.strlen($key).' chars)';
    }
}

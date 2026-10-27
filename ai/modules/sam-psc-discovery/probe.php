<?php

/**
 * Phase 1 probe (read-only): how does SAM.gov's `ccode` parameter match PSCs?
 *
 * Usage (from project root):
 *   php ai/modules/sam-psc-discovery/probe.php <label> <days_back> <ccode,...>
 *   php ai/modules/sam-psc-discovery/probe.php naics <label> <days_back> <ncode,...>
 *
 * Writes ai/modules/sam-psc-discovery/probe-<label>.json with, per code:
 * totalRecords, returned, distinct classificationCode counts, notice IDs,
 * sample titles, duration and rate-limit-looking response headers.
 */

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$args = array_slice($argv, 1);
$param = 'ccode';
if (($args[0] ?? null) === 'naics') {
    $param = 'ncode';
    array_shift($args);
}

[$label, $days, $codes] = [$args[0] ?? 'probe', (int) ($args[1] ?? 90), explode(',', $args[2] ?? '5305')];

$out = ['param' => $param, 'days_back' => $days, 'run_at' => now()->toIso8601String(), 'codes' => []];

foreach ($codes as $code) {
    $rows = [];
    $total = null;
    $offset = 0;
    $calls = 0;
    $headers = [];
    $start = microtime(true);
    $error = null;

    do {
        $resp = Http::timeout(60)->get(config('services.sam.base_url'), [
            'api_key' => config('services.sam.api_key'),
            'postedFrom' => now()->subDays($days)->format('m/d/Y'),
            'postedTo' => now()->format('m/d/Y'),
            $param => $code,
            'ptype' => 'o,k,p,r',
            'limit' => 1000,
            'offset' => $offset,
        ]);
        $calls++;

        $headers = collect($resp->headers())
            ->filter(fn ($v, $k) => preg_match('/rate|limit|remaining|retry/i', $k))
            ->map(fn ($v) => implode(',', $v))
            ->all();

        if (! $resp->ok()) {
            $error = ['status' => $resp->status(), 'body' => substr($resp->body(), 0, 300)];
            break;
        }

        $page = $resp->json('opportunitiesData') ?? [];
        $total = $resp->json('totalRecords');
        $rows = array_merge($rows, $page);
        $offset++; // SAM `offset` is a zero-based page index, not a record offset
        usleep(300000);
    } while (count($page) === 1000 && $offset < 10);

    $classes = [];
    foreach ($rows as $r) {
        $c = (string) ($r['classificationCode'] ?? 'none');
        $classes[$c] = ($classes[$c] ?? 0) + 1;
    }
    arsort($classes);

    $out['codes'][$code] = [
        'total' => $total,
        'returned' => count($rows),
        'calls' => $calls,
        'duration_ms' => (int) ((microtime(true) - $start) * 1000),
        'error' => $error,
        'classification_codes' => $classes,
        'notice_ids' => array_values(array_filter(array_map(fn ($r) => $r['noticeId'] ?? null, $rows))),
        'rows' => array_map(fn ($r) => [
            'id' => $r['noticeId'] ?? null,
            'title' => mb_substr($r['title'] ?? '', 0, 80),
            'naics' => $r['naicsCode'] ?? null,
            'psc' => $r['classificationCode'] ?? null,
            'agency' => $r['fullParentPathName'] ?? null,
            'type' => $r['type'] ?? null,
            'posted' => $r['postedDate'] ?? null,
            'due' => $r['responseDeadLine'] ?? null,
            'set_aside' => $r['typeOfSetAside'] ?? null,
            'state' => $r['placeOfPerformance']['state']['code'] ?? null,
        ], $rows),
        'sample_titles' => array_slice(array_map(fn ($r) => ($r['classificationCode'] ?? '?').' | '.($r['naicsCode'] ?? '?').' | '.mb_substr($r['title'] ?? '', 0, 60), $rows), 0, 15),
        'rate_headers' => $headers,
    ];

    fwrite(STDERR, sprintf("%-6s total=%-6s returned=%-5s calls=%d %s\n", $code, $total ?? 'ERR', count($rows), $calls, $error ? json_encode($error) : ''));
}

file_put_contents(__DIR__."/probe-{$label}.json", json_encode($out, JSON_PRETTY_PRINT));

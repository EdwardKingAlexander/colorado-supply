<?php

// Read-only investigation: no jobs, state writes, database writes, or notifications.
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['logging.default' => 'null']);

if (in_array('--live', $argv, true)) {
    $endpoint = config('services.sam.base_url');
    if (! in_array(parse_url($endpoint, PHP_URL_HOST), ['sam.gov', 'api.sam.gov'], true)) {
        exit("Unexpected endpoint host; no credential sent.\n");
    }
    $base = [
        'api_key' => config('services.sam.api_key'),
        'postedFrom' => '09/01/2026', 'postedTo' => '09/30/2026',
        'ncode' => '339113', 'limit' => 2, 'ptype' => 'k,o,s,p',
    ];
    if (! $base['api_key']) {
        exit("No configured key; live probes skipped.\n");
    }
    $probes = [
        'page_zero' => ['offset' => 0],
        'page_one' => ['offset' => 1],
        'oversized_page' => ['offset' => 1000],
        'first_four' => ['offset' => 0, 'limit' => 4],
    ];
    if (in_array('--counts', $argv, true)) {
        $probes = [
            'combined_types' => ['ptype' => 'k,o,s,p'],
            'combined_synopsis_only' => ['ptype' => 'k'],
            'solicitation_only' => ['ptype' => 'o'],
            'sources_sought_only' => ['ptype' => 's'],
            'presolicitation_only' => ['ptype' => 'p'],
            'unrestricted_naics' => ['ncode' => null],
            'industrial_supplies' => ['ncode' => '423840'],
        ];
    }
    foreach ($probes as $label => $overrides) {
        $query = array_filter(array_replace($base, ['offset' => 0], $overrides), fn ($value) => $value !== null);
        try {
            $response = Illuminate\Support\Facades\Http::timeout(15)->get($endpoint, $query);
            $body = $response->json();
            echo json_encode([
                'probe' => $label, 'offset' => $query['offset'], 'limit' => $query['limit'], 'status' => $response->status(),
                'total_records' => $body['totalRecords'] ?? null,
                'returned' => count($body['opportunitiesData'] ?? []),
                'notice_ids' => array_column($body['opportunitiesData'] ?? [], 'noticeId'),
            ]).PHP_EOL;
            if (! $response->successful()) {
                break;
            }
        } catch (Throwable $e) {
            echo json_encode(['probe' => $label, 'error_class' => get_class($e)]).PHP_EOL;
            break;
        }
    }
    exit;
}

config(['services.sam.base_url' => 'https://sam.test/opportunities/v2/search', 'services.sam.max_pages' => 10]);
Illuminate\Support\Facades\Http::preventStrayRequests();
$offsets = [];
Illuminate\Support\Facades\Http::fake(function ($request) use (&$offsets) {
    $page = (int) $request['offset'];
    $limit = (int) $request['limit'];
    $offsets[] = $page;
    $records = [];
    // GSA contract: offset is a zero-based PAGE index, not a record offset.
    for ($i = $page * $limit; $i < min(($page + 1) * $limit, 2500); $i++) {
        $records[] = ['noticeId' => 'notice-'.$i, 'title' => 'Opportunity '.$i];
    }
    return Illuminate\Support\Facades\Http::response(['totalRecords' => 2500, 'opportunitiesData' => $records]);
});
$result = (new App\Services\SamApiClient)->fetch('339113', [
    'posted_from' => '09/01/2026', 'posted_to' => '09/30/2026',
    'notice_type_codes' => ['o'], 'place' => null,
], 'fake-key');
echo json_encode([
    'requested_page_indexes' => $offsets,
    'expected_records' => 2500,
    'actual_records' => $result['count'],
    'missing_records' => 2500 - $result['count'],
    'reported_success' => $result['success'],
    'reported_truncated' => $result['truncated'],
], JSON_PRETTY_PRINT).PHP_EOL;

<?php

declare(strict_types=1);

use App\Jobs\FetchSamOpportunitiesJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('job dispatches successfully', function () {
    Queue::fake();

    FetchSamOpportunitiesJob::dispatch(
        params: ['clearCache' => true, 'limit' => 100],
        userId: 1
    );

    Queue::assertPushed(FetchSamOpportunitiesJob::class);
});

test('job executes and persists results', function () {
    Config::set('services.sam.api_key', 'test-api-key');

    // Mock SAM.gov API responses
    Http::fake([
        '*naics=423840*' => Http::response([
            'opportunitiesData' => [
                [
                    'noticeId' => 'test-001',
                    'title' => 'Test Opportunity',
                    'naicsCode' => '423840',
                    'postedDate' => '2025-11-28',
                ],
            ],
            'totalRecords' => 1,
        ], 200),
        '*' => Http::response(['opportunitiesData' => []], 200),
    ]);

    // Execute job
    $job = new FetchSamOpportunitiesJob(
        params: ['naics_override' => ['423840'], 'days_back' => 7, 'limit' => 100],
        userId: 1
    );

    $job->handle();

    // Verify state file was created
    $stateFile = app_path('Mcp/Servers/Business/State/sam-opportunities.json');
    expect(file_exists($stateFile))->toBeTrue();

    $result = json_decode(file_get_contents($stateFile), true);
    expect($result)
        ->toHaveKey('success')
        ->and($result['success'])->toBeTrue()
        ->and($result['summary']['total_after_dedup'])->toBeGreaterThanOrEqual(0);
});

test('job persists partial success when some NAICS fail', function () {
    Config::set('services.sam.api_key', 'test-api-key');

    // Mock API to return 500 error for all requests
    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    $job = new FetchSamOpportunitiesJob(
        params: ['naics_override' => ['423840'], 'days_back' => 7, 'limit' => 100],
        userId: 1
    );

    // Job should complete even with API errors (partial success)
    $job->handle();

    // Verify state file was created with failure status
    $stateFile = app_path('Mcp/Servers/Business/State/sam-opportunities.json');
    expect(file_exists($stateFile))->toBeTrue();

    $result = json_decode(file_get_contents($stateFile), true);
    expect($result)
        ->toHaveKey('success')
        ->and($result['success'])->toBeFalse()
        ->and($result['summary']['failed_naics_count'])->toBeGreaterThan(0);
});

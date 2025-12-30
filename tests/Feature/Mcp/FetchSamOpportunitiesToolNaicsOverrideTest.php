<?php

declare(strict_types=1);

use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('tool respects naics_override parameter', function () {
    Config::set('services.sam.api_key', 'test-api-key');

    // Track what URLs are actually called
    $calledUrls = [];

    // Mock only 3 NAICS codes
    Http::fake([
        '*naics=111111*' => Http::response(['opportunitiesData' => []], 200),
        '*naics=222222*' => Http::response(['opportunitiesData' => []], 200),
        '*naics=333333*' => Http::response(['opportunitiesData' => []], 200),
        '*' => function ($request) use (&$calledUrls) {
            $calledUrls[] = $request->url();

            return Http::response(['opportunitiesData' => []], 200);
        },
    ]);

    $tool = new FetchSamOpportunitiesTool;
    $result = $tool->fetch([
        'naics_override' => ['111111', '222222', '333333'],
        'days_back' => 1,
    ]);

    // Debug: Show what URLs were actually called
    if (count($calledUrls) > 3) {
        dump('Extra URLs called:', $calledUrls);
    }

    // Verify the query metadata shows 3 NAICS codes
    dump('Query NAICS codes:', $result['query']['naics_codes'] ?? 'NOT SET');

    expect($result['query']['naics_codes'])->toHaveCount(3)
        ->and($result['query']['naics_codes'])->toBe(['111111', '222222', '333333']);

    // Verify that ONLY 3 HTTP requests were made (one per override NAICS)
    Http::assertSentCount(3);
});

<?php

declare(strict_types=1);

use App\Services\SamApiClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| 404 disambiguation
|--------------------------------------------------------------------------
|
| SAM.gov uses 404 for two unrelated things: "no records matched" (documented
| by GSA, arrives with a JSON body) and, during the 2026-07-09 gateway outage,
| "this route does not exist" (empty body). Collapsing them either reports an
| empty result as a system failure, or hides a real outage behind a plausible
| zero. The body is the discriminator.
|
*/

beforeEach(function () {
    Config::set('services.sam.base_url', 'https://sam.test/opportunities/v2/search');
});

function samParams(): array
{
    return [
        'posted_from' => '08/01/2026',
        'posted_to' => '08/24/2026',
        'notice_type_codes' => ['o'],
        'place' => null,
        'set_aside_codes' => [],
    ];
}

it('treats a 404 with a JSON body as an empty result, not a failure', function () {
    Http::fake(['*' => Http::response(['totalRecords' => 0, 'opportunitiesData' => []], 404)]);

    $result = (new SamApiClient)->fetch('423840', samParams(), 'key');

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(0)
        ->and($result['opportunities'])->toBe([])
        ->and($result['no_data'] ?? false)->toBeTrue();
});

it('treats a 404 with an empty body as a hard endpoint failure', function () {
    Http::fake(['*' => Http::response('', 404)]);

    $result = (new SamApiClient)->fetch('423840', samParams(), 'key');

    // This is the regression guard for the outage. It must never be reported
    // as "0 results found".
    expect($result['success'])->toBeFalse()
        ->and($result['error_type'])->toBe('endpoint_unreachable')
        ->and($result['status_code'])->toBe(404)
        ->and($result['error'])->toContain('sam.test')
        ->and($result['error'])->toContain('sam:diagnose');
});

it('keeps a non-JSON 404 body loud rather than calling it no-data', function () {
    Http::fake(['*' => Http::response('Not Found', 404)]);

    $result = (new SamApiClient)->fetch('423840', samParams(), 'key');

    expect($result['success'])->toBeFalse();
});

it('still reports credential failures distinctly', function () {
    Http::fake(['*' => Http::response('Unauthorized', 401)]);

    $result = (new SamApiClient)->fetch('423840', samParams(), 'key');

    expect($result['success'])->toBeFalse()
        ->and($result['status_code'])->toBe(401)
        ->and($result['error_type'])->toBe('authentication');
});

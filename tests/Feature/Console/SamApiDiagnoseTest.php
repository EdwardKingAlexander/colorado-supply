<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.sam.base_url', 'https://sam.test/opportunities/v2/search');
    Config::set('services.sam.api_key', 'c94b240c-1ac0-4a1e-b0c3-000000000000');
});

it('exits successfully and reports totalRecords when the endpoint is healthy', function () {
    Http::fake([
        'sam.test/*' => Http::response(['totalRecords' => 24084, 'opportunitiesData' => []], 200),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('sam:diagnose')
        ->expectsOutputToContain('Endpoint healthy')
        ->assertExitCode(0);
});

it('exits non-zero and names the outage signature on an empty-bodied 404', function () {
    Http::fake([
        'sam.test/*' => Http::response('', 404),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('sam:diagnose')
        ->expectsOutputToContain('Endpoint unreachable')
        ->assertExitCode(1);
});

it('reports no-data as healthy when a 404 carries a body', function () {
    Http::fake([
        'sam.test/*' => Http::response(['totalRecords' => 0], 404),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('sam:diagnose')
        ->expectsOutputToContain('No matching records')
        ->assertExitCode(0);
});

it('distinguishes a rejected credential from a missing route', function () {
    Http::fake([
        'sam.test/*' => Http::response('Unauthorized', 401),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('sam:diagnose')
        ->expectsOutputToContain('Credential rejected')
        ->expectsOutputToContain('Request Public API Key')
        ->assertExitCode(1);
});

it('blames the old key format, not the route, when SAM.gov answers 400 with and without a key', function () {
    // Live signature, 2026-11-13: both probes 400, but with different bodies.
    Http::fake(function ($request) {
        if (! str_contains($request->url(), 'sam.test')) {
            return Http::response('ok', 200);
        }

        return str_contains($request->url(), 'api_key=')
            ? Http::response(['detail' => 'Validation failure', 'status' => 400, 'title' => 'Bad Request'], 400)
            : Http::response(['detail' => "Required parameter 'api_key' is not present.", 'status' => 400], 400);
    });

    $this->artisan('sam:diagnose')
        ->expectsOutputToContain('bare UUID (old format')
        ->expectsOutputToContain('API key rejected (old key format)')
        ->expectsOutputToContain('Request Public API Key')
        ->doesntExpectOutputToContain('the route itself is the problem')
        ->assertExitCode(1);
});

it('does not blame the key format for a 400 when the key is current-format', function () {
    Config::set('services.sam.api_key', 'SAM-c94b240c-1ac0-4a1e-b0c3-000000000000');

    Http::fake(function ($request) {
        if (! str_contains($request->url(), 'sam.test')) {
            return Http::response('ok', 200);
        }

        return str_contains($request->url(), 'api_key=')
            ? Http::response(['detail' => 'Invalid Date Entered', 'status' => 400], 400)
            : Http::response(['detail' => "Required parameter 'api_key' is not present.", 'status' => 400], 400);
    });

    $this->artisan('sam:diagnose')
        ->expectsOutputToContain('SAM- prefixed (current SAM.gov format)')
        ->expectsOutputToContain('Unexpected HTTP 400')
        ->expectsOutputToContain('Invalid Date Entered')
        ->doesntExpectOutputToContain('old key format')
        ->assertExitCode(1);
});

it('never prints the API key', function () {
    Http::fake([
        'sam.test/*' => Http::response(['totalRecords' => 1], 200),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('sam:diagnose')
        ->doesntExpectOutputToContain('c94b240c-1ac0-4a1e-b0c3-000000000000')
        ->assertExitCode(0);
});

it('emits machine-readable json for monitoring without leaking the key', function () {
    Http::fake([
        'sam.test/*' => Http::response(['totalRecords' => 7], 200),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('sam:diagnose --json')
        ->doesntExpectOutputToContain('c94b240c-1ac0-4a1e-b0c3-000000000000')
        ->expectsOutputToContain('"healthy": true')
        ->assertExitCode(0);
});

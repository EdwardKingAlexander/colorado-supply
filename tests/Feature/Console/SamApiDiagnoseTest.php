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

<?php

use App\Support\Dashboard\DashboardDateRange;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

test('it builds a default last thirty days range', function () {
    $range = DashboardDateRange::fromFilters([]);

    expect($range->key)->toBe('last_30_days')
        ->and($range->start->toDateString())->toBe(now()->subDays(29)->toDateString())
        ->and($range->end->toDateString())->toBe(now()->toDateString());
});

test('it builds a custom range', function () {
    $range = DashboardDateRange::fromFilters([
        'range' => 'custom',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]);

    expect($range->key)->toBe('custom')
        ->and($range->start->toDateString())->toBe('2026-01-01')
        ->and($range->end->toDateString())->toBe('2026-01-31');
});

test('it rejects custom ranges without dates', function () {
    DashboardDateRange::fromFilters(['range' => 'custom']);
})->throws(ValidationException::class);

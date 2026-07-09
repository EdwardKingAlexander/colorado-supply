<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class DashboardDateRange
{
    public function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    /**
     * @param  array{range?: string|null, start_date?: string|null, end_date?: string|null}  $filters
     */
    public static function fromFilters(array $filters): self
    {
        $timezone = config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);
        $range = $filters['range'] ?? 'last_30_days';

        return match ($range) {
            'this_month' => new self($range, $now->startOfMonth(), $now->endOfDay()),
            'quarter_to_date' => new self($range, $now->firstOfQuarter()->startOfDay(), $now->endOfDay()),
            'year_to_date' => new self($range, $now->startOfYear(), $now->endOfDay()),
            'last_12_months' => new self($range, $now->subMonthsNoOverflow(12)->startOfDay(), $now->endOfDay()),
            'custom' => self::custom($filters, $timezone),
            default => new self('last_30_days', $now->subDays(29)->startOfDay(), $now->endOfDay()),
        };
    }

    /**
     * @param  array{start_date?: string|null, end_date?: string|null}  $filters
     */
    private static function custom(array $filters, string $timezone): self
    {
        if (blank($filters['start_date'] ?? null) || blank($filters['end_date'] ?? null)) {
            throw ValidationException::withMessages([
                'start_date' => 'Start and end dates are required for custom ranges.',
            ]);
        }

        $start = CarbonImmutable::parse($filters['start_date'], $timezone)->startOfDay();
        $end = CarbonImmutable::parse($filters['end_date'], $timezone)->endOfDay();

        if ($start->greaterThan($end)) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be after or equal to the start date.',
            ]);
        }

        return new self('custom', $start, $end);
    }

    public function bucketFormat(): string
    {
        return $this->start->diffInDays($this->end) > 92 ? 'Y-m' : 'Y-m-d';
    }

    public function bucketLabel(string $bucket): string
    {
        $date = CarbonImmutable::parse($bucket, config('app.timezone', 'UTC'));

        return $this->bucketFormat() === 'Y-m' ? $date->format('M Y') : $date->format('M j');
    }

    /**
     * @return array{key: string, start_date: string, end_date: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'start_date' => $this->start->toDateString(),
            'end_date' => $this->end->toDateString(),
        ];
    }
}

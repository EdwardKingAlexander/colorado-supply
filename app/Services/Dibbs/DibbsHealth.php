<?php

namespace App\Services\Dibbs;

use App\Models\DibbsFile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Is DIBBS ingestion healthy? (ai/modules/dibbs-rfq-tracking, Phase 7)
 *
 * DLA posts RFQ files on business days, so "stale" counts weekdays only.
 * Federal holidays are not modelled: a holiday can add one false "behind" day.
 */
class DibbsHealth
{
    public const LAST_RUN_KEY = 'dibbs:last_run';

    public const FAILURES_KEY = 'dibbs:consecutive_failures';

    /** Business days without a new index file before ingestion counts as stale. */
    public const STALE_AFTER_BUSINESS_DAYS = 2;

    /**
     * Called by DibbsDownloadJob at the end of every run.
     */
    public static function recordRun(bool $ok, array $summary, ?string $error = null): void
    {
        Cache::forever(self::LAST_RUN_KEY, [
            'at' => now()->toIso8601String(),
            'ok' => $ok,
            'downloaded' => count($summary['downloaded'] ?? []),
            'missing' => count($summary['missing'] ?? []),
            'failed' => array_slice($summary['failed'] ?? [], 0, 5),
            'error' => $error,
        ]);

        $ok ? Cache::forever(self::FAILURES_KEY, 0) : Cache::forever(self::FAILURES_KEY, (int) Cache::get(self::FAILURES_KEY, 0) + 1);
    }

    /**
     * @return array{latest_file: ?DibbsFile, business_days_behind: ?int, stale: bool, last_run: ?array, consecutive_failures: int, next_run: CarbonImmutable, enabled: bool}
     */
    public function status(): array
    {
        $latest = DibbsFile::where('type', DibbsFile::TYPE_INDEX)
            ->where('status', DibbsFile::STATUS_IMPORTED)
            ->orderByDesc('posting_date')
            ->first();

        $behind = $latest?->posting_date ? $this->businessDaysBetween(CarbonImmutable::parse($latest->posting_date), CarbonImmutable::today()) : null;

        return [
            'latest_file' => $latest,
            'business_days_behind' => $behind,
            // DLA posts a day's file the next morning, so one business day behind is normal.
            'stale' => $behind === null || $behind > self::STALE_AFTER_BUSINESS_DAYS,
            'last_run' => Cache::get(self::LAST_RUN_KEY),
            'consecutive_failures' => (int) Cache::get(self::FAILURES_KEY, 0),
            'next_run' => $this->nextRun(),
            'enabled' => (bool) config('dibbs.enabled'),
        ];
    }

    /**
     * Weekdays after $from up to and including $to.
     */
    public function businessDaysBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $days = 0;

        for ($day = $from->addDay(); $day->lte($to); $day = $day->addDay()) {
            if ($day->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Next scheduled download (07:00 America/Denver), in app time.
     */
    public function nextRun(): CarbonImmutable
    {
        $next = CarbonImmutable::now('America/Denver')->setTime(7, 0);

        if ($next->isPast()) {
            $next = $next->addDay();
        }

        return $next->setTimezone(config('app.timezone'));
    }
}

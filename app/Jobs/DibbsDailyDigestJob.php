<?php

namespace App\Jobs;

use App\Models\DibbsFile;
use App\Notifications\DibbsDailyDigestNotification;
use App\Services\Dibbs\DibbsDigestBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends the morning DIBBS digest (ai/modules/dibbs-rfq-tracking, Phase 5).
 *
 * Runs right after the daily download imports a new index file, and again as
 * a fallback later in the morning. Rules:
 * - each posting file's new RFQs are reported once (dibbs_files.digest_sent_at);
 * - at most one digest per calendar day;
 * - with no new file, only send when something is closing soon;
 * - never send an empty digest.
 */
class DibbsDailyDigestJob implements ShouldQueue
{
    use Queueable;

    public const SENT_TODAY_KEY = 'dibbs:digest:last_sent_on';

    /**
     * @return string outcome, for logs and tests
     */
    public function handle(DibbsDigestBuilder $builder): string
    {
        if (! config('dibbs.digest.enabled')) {
            return 'disabled';
        }

        $recipients = config('dibbs.digest.recipients') ?: array_filter([config('dibbs.alert_email')]);

        if ($recipients === []) {
            Log::warning('DIBBS digest skipped: no recipients', ['hint' => 'Set DIBBS_DIGEST_EMAILS (or DIBBS_ALERT_EMAIL) in .env']);

            return 'no_recipients';
        }

        $today = now()->toDateString();

        if (Cache::get(self::SENT_TODAY_KEY) === $today) {
            return 'already_sent_today';
        }

        $latest = DibbsFile::where('type', DibbsFile::TYPE_INDEX)
            ->where('status', DibbsFile::STATUS_IMPORTED)
            ->orderByDesc('posting_date')
            ->first();

        $newFile = $latest && $latest->digest_sent_at === null ? $latest : null;
        $digest = $builder->build($newFile);

        if ($digest['is_empty']) {
            $newFile?->update(['digest_sent_at' => now()]); // nothing to say about this file; don't revisit it

            return 'empty';
        }

        try {
            Notification::route('mail', $recipients)->notify(new DibbsDailyDigestNotification($digest));
        } catch (Throwable $e) {
            // Not marked sent: the fallback run retries.
            Log::error('DIBBS digest failed to send', ['error' => $e->getMessage()]);

            return 'send_failed';
        }

        $newFile?->update(['digest_sent_at' => now()]);
        Cache::put(self::SENT_TODAY_KEY, $today, now()->addDays(2));

        Log::info('DIBBS digest sent', [
            'posting_date' => $digest['posting_date'],
            'sections' => array_map(fn ($s) => $s['total'], $digest['sections']),
            'recipients' => count($recipients),
        ]);

        return 'sent';
    }
}

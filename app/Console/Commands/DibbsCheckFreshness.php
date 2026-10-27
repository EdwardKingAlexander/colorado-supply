<?php

namespace App\Console\Commands;

use App\Notifications\DibbsIngestionFailedNotification;
use App\Services\Dibbs\DibbsHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Alerts when no new DIBBS RFQ file has been imported for too long
 * (ai/modules/dibbs-rfq-tracking, Phase 7). Catches what per-run alerts cannot:
 * the scheduler or queue worker not running at all.
 */
class DibbsCheckFreshness extends Command
{
    protected $signature = 'dibbs:check-freshness';

    protected $description = 'Alert if no new DIBBS RFQ file has been imported for more than 2 business days';

    public function handle(DibbsHealth $health): int
    {
        if (! config('dibbs.enabled')) {
            $this->info('DIBBS ingestion is disabled; nothing to check.');

            return self::SUCCESS;
        }

        $status = $health->status();

        if (! $status['stale']) {
            $this->info('DIBBS ingestion is current (latest file '.$status['latest_file']->posting_date->format('M j').', '.$status['business_days_behind'].' business day(s) ago).');

            return self::SUCCESS;
        }

        $reason = $status['latest_file']
            ? 'No new DIBBS RFQ file since '.$status['latest_file']->posting_date->format('M j, Y').' ('.$status['business_days_behind'].' business days).'
            : 'No DIBBS RFQ file has ever been imported.';

        $details = array_values(array_filter([
            $status['last_run'] ? 'Last download run: '.$status['last_run']['at'].($status['last_run']['ok'] ? ' (ok)' : ' (failed: '.($status['last_run']['error'] ?? implode('; ', $status['last_run']['failed'])).')') : 'The download job has not run (is the scheduler running?).',
            $status['consecutive_failures'] > 0 ? $status['consecutive_failures'].' consecutive failed run(s).' : null,
        ]));

        $this->warn($reason);

        $recipient = config('dibbs.alert_email');

        if (! $recipient) {
            $this->warn('No alert recipient configured (DIBBS_ALERT_EMAIL).');

            return self::FAILURE;
        }

        try {
            Notification::route('mail', $recipient)->notify(new DibbsIngestionFailedNotification($reason, $details));
        } catch (Throwable $e) {
            $this->error('Could not send the alert: '.$e->getMessage());
        }

        return self::FAILURE;
    }
}

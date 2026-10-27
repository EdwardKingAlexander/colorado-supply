<?php

namespace App\Console\Commands;

use App\Jobs\DibbsDownloadJob;
use Illuminate\Console\Command;

/**
 * Run the DIBBS download now (ai/modules/dibbs-rfq-tracking).
 *
 * Same job the 07:00 schedule queues, run immediately in this process. Use
 * `--days=25` once on a fresh install to pull everything DIBBS still lists
 * (about a month of posting days) instead of the usual 5.
 */
class DibbsDownload extends Command
{
    protected $signature = 'dibbs:download
        {--days= : How many of the most recent posting days to catch up on (default config dibbs.backfill_days, max 30)}
        {--force : Run even when DIBBS_ENABLED=false}';

    protected $description = 'Download and import DIBBS RFQ files now';

    public function handle(): int
    {
        $days = $this->option('days');

        if ($days !== null) {
            if (! ctype_digit((string) $days) || (int) $days < 1 || (int) $days > 30) {
                $this->error('--days must be a whole number from 1 to 30.');

                return self::INVALID;
            }

            config(['dibbs.backfill_days' => (int) $days]);
        }

        if (! config('dibbs.enabled')) {
            if (! $this->option('force')) {
                $this->warn('DIBBS downloads are disabled (DIBBS_ENABLED=false). Use --force to run anyway.');

                return self::FAILURE;
            }

            config(['dibbs.enabled' => true]);
        }

        $this->info('Checking the last '.config('dibbs.backfill_days').' posting day(s) on DIBBS (about 2 seconds between requests)…');

        $summary = app()->call([new DibbsDownloadJob, 'handle']);

        if (isset($summary['error'])) {
            $this->error('Could not read the DIBBS RFQ listing: '.$summary['error']);

            return self::FAILURE;
        }

        $this->table(['Result', 'Files'], [
            ['Imported', count($summary['downloaded']).($summary['downloaded'] ? ': '.implode(', ', $summary['downloaded']) : '')],
            ['Already imported', count($summary['skipped'])],
            ['Not posted yet', count($summary['missing']).($summary['missing'] ? ': '.implode(', ', $summary['missing']) : '')],
            ['Failed', count($summary['failed'])],
        ]);

        foreach ($summary['failed'] as $failure) {
            $this->error($failure);
        }

        if ($summary['downloaded'] !== []) {
            $this->line('Catalog matches were rebuilt and the morning digest was queued.');
        }

        return $summary['failed'] === [] ? self::SUCCESS : self::FAILURE;
    }
}

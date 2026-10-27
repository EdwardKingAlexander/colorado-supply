<?php

namespace App\Jobs;

use App\Models\DibbsFile;
use App\Notifications\DibbsIngestionFailedNotification;
use App\Services\Dibbs\DibbsClient;
use App\Services\Dibbs\DibbsFileImporter;
use App\Services\Dibbs\DibbsHealth;
use App\Services\Dibbs\DibbsRfqMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Downloads and imports DIBBS daily RFQ files not yet imported (ai/modules/dibbs-rfq-tracking).
 *
 * Catches up on the most recent config('dibbs.backfill_days') posting days on
 * every run, so a missed day (outage, worker down) heals on the next run.
 */
class DibbsDownloadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    /**
     * @return array{downloaded: array<int, string>, skipped: array<int, string>, missing: array<int, string>, failed: array<int, string>}
     */
    public function handle(DibbsClient $client, DibbsFileImporter $importer): array
    {
        $summary = ['downloaded' => [], 'skipped' => [], 'missing' => [], 'failed' => []];

        if (! config('dibbs.enabled')) {
            Log::info('DIBBS download skipped: disabled (DIBBS_ENABLED=false)');

            return $summary;
        }

        try {
            $days = array_slice($client->listRecentFiles(), 0, max(1, (int) config('dibbs.backfill_days', 5)));
        } catch (Throwable $e) {
            DibbsHealth::recordRun(false, $summary, 'Could not read the DIBBS RFQ listing: '.$e->getMessage());
            $this->alert('Could not read the DIBBS RFQ listing: '.$e->getMessage());

            return $summary + ['error' => $e->getMessage()];
        }

        foreach ($days as $day) {
            foreach (['in', 'bq'] as $type) {
                $url = $day[$type];

                if ($url === null) {
                    continue;
                }

                $name = strtolower(basename(parse_url($url, PHP_URL_PATH)));

                if (DibbsFile::where('name', $name)->where('status', DibbsFile::STATUS_IMPORTED)->exists()) {
                    $summary['skipped'][] = $name;

                    continue;
                }

                try {
                    $contents = $client->download($url);

                    if ($contents === null) {
                        $summary['missing'][] = $name;

                        continue;
                    }

                    $file = $importer->import($importer->register($contents, $name, 'automated'));

                    $file->isImported()
                        ? $summary['downloaded'][] = $name
                        : $summary['failed'][] = "{$name}: {$file->error}";
                } catch (Throwable $e) {
                    $summary['failed'][] = "{$name}: {$e->getMessage()}";
                }
            }
        }

        if ($summary['downloaded'] !== []) {
            try {
                app(DibbsRfqMatcher::class)->rebuild();
            } catch (Throwable $e) {
                $summary['failed'][] = 'matching: '.$e->getMessage();
            }
        }

        // New RFQs are matched; tell people about them.
        if (array_filter($summary['downloaded'], fn ($name) => str_starts_with($name, 'in')) !== []) {
            DibbsDailyDigestJob::dispatch();
        }

        Log::info('DIBBS download finished', $summary);
        DibbsHealth::recordRun($summary['failed'] === [], $summary);

        if ($summary['failed'] !== []) {
            $this->alert(count($summary['failed']).' DIBBS file(s) could not be imported.', $summary['failed']);
        }

        return $summary;
    }

    /**
     * Best-effort: a mail problem must never mask the ingestion failure itself.
     */
    protected function alert(string $reason, array $failures = []): void
    {
        Log::error('DIBBS ingestion failed', ['reason' => $reason, 'failures' => $failures]);

        $recipient = config('dibbs.alert_email');

        if (empty($recipient)) {
            Log::warning('DIBBS ingestion failed but no alert recipient is configured', ['hint' => 'Set DIBBS_ALERT_EMAIL or SAM_ALERT_EMAIL in .env']);

            return;
        }

        try {
            Notification::route('mail', $recipient)->notify(new DibbsIngestionFailedNotification($reason, $failures));
        } catch (Throwable $e) {
            Log::error('Failed to send DIBBS ingestion alert', ['error' => $e->getMessage()]);
        }
    }
}

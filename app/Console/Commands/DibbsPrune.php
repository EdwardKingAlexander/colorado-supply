<?php

namespace App\Console\Commands;

use App\Models\DibbsApprovedSource;
use App\Models\DibbsFile;
use App\Models\DibbsQuoteTemplate;
use App\Models\DibbsRfq;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps DIBBS data bounded (ai/modules/dibbs-rfq-tracking, Phase 7).
 *
 * Deletes RFQs closed more than N days ago that nobody acted on (no tracking
 * beyond "new") and that match nothing in our catalog, with their batch quote
 * rows; then approved sources no remaining RFQ refers to; then raw downloaded
 * files older than the raw-file window (the database rows stay).
 */
class DibbsPrune extends Command
{
    protected $signature = 'dibbs:prune
        {--days= : Prune RFQs closed more than this many days ago (default config dibbs.retention.closed_rfq_days)}
        {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Delete old, untouched DIBBS RFQs and raw files';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('dibbs.retention.closed_rfq_days', 180));
        $rawDays = (int) config('dibbs.retention.raw_file_days', 60);
        $dry = (bool) $this->option('dry-run');

        $prunable = DibbsRfq::query()
            ->where('return_by_date', '<', today()->subDays($days)->toDateString())
            // Any company's activity or match keeps an RFQ.
            ->whereDoesntHave('allTrackings', fn (Builder $t) => $t->where('status', '!=', 'new'))
            ->whereDoesntHave('allMatches');

        $rfqCount = (clone $prunable)->count();

        $rawFiles = DibbsFile::query()
            ->where('status', DibbsFile::STATUS_IMPORTED)
            ->whereNotNull('stored_path')
            ->where('imported_at', '<', now()->subDays($rawDays));

        $rawCount = (clone $rawFiles)->count();

        if ($dry) {
            $this->info("Would delete {$rfqCount} RFQ(s) closed more than {$days} days ago (untouched, unmatched), their batch quote rows and orphaned approved sources, and {$rawCount} raw file(s) older than {$rawDays} days.");

            return self::SUCCESS;
        }

        $deletedRfqs = 0;

        (clone $prunable)->select('id')->chunkById(1000, function ($rfqs) use (&$deletedRfqs) {
            $ids = $rfqs->pluck('id');

            DB::transaction(function () use ($ids, &$deletedRfqs) {
                DibbsQuoteTemplate::whereIn('dibbs_rfq_id', $ids)->delete();
                $deletedRfqs += DibbsRfq::whereIn('id', $ids)->delete();
            });
        });

        $orphanSources = DibbsApprovedSource::query()
            ->whereNotIn('nsn', DibbsRfq::select('nsn'))
            ->delete();

        $deletedFiles = 0;

        foreach ($rawFiles->get() as $file) {
            Storage::disk('local')->delete($file->stored_path);
            $file->update(['stored_path' => null]);
            $deletedFiles++;
        }

        $this->info("Deleted {$deletedRfqs} RFQ(s), {$orphanSources} orphaned approved source(s) and {$deletedFiles} raw file(s).");

        return self::SUCCESS;
    }
}

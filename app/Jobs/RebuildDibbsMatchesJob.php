<?php

namespace App\Jobs;

use App\Services\Dibbs\DibbsRfqMatcher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Recomputes DIBBS RFQ matches after catalog changes (parts, manufacturers,
 * suppliers). Unique so a burst of edits queues one rebuild.
 */
class RebuildDibbsMatchesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    /** Set while a bulk operation rebuilds once itself (e.g. the catalog import). */
    protected static bool $suspended = false;

    public function handle(DibbsRfqMatcher $matcher): void
    {
        $matcher->rebuild();
    }

    /**
     * Queue a rebuild unless a bulk operation has suspended them.
     */
    public static function request(): void
    {
        if (! static::$suspended) {
            static::dispatch()->afterCommit();
        }
    }

    /**
     * Run a bulk catalog change without queuing one rebuild per row.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutRebuilds(callable $callback): mixed
    {
        $previous = static::$suspended;
        static::$suspended = true;

        try {
            return $callback();
        } finally {
            static::$suspended = $previous;
        }
    }
}

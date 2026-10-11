<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the bell's notifications table bounded
 * (ai/modules/will-call-notifications, phase 2): read notifications go after
 * 90 days, unread ones after 180.
 */
class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune
        {--read-days=90 : Delete read notifications older than this many days}
        {--unread-days=180 : Delete unread notifications older than this many days}';

    protected $description = 'Delete old in-app (bell) notifications';

    public function handle(): int
    {
        $readDays = max(1, (int) $this->option('read-days'));
        $unreadDays = max(1, (int) $this->option('unread-days'));

        $read = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays($readDays))
            ->delete();

        $unread = DB::table('notifications')
            ->whereNull('read_at')
            ->where('created_at', '<', now()->subDays($unreadDays))
            ->delete();

        $this->info("Deleted {$read} read and {$unread} unread notifications.");

        return self::SUCCESS;
    }
}

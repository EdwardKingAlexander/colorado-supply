<?php

namespace App\Listeners;

use App\Models\Admin;
use App\Notifications\AppNotification;
use App\Notifications\NotificationChannels;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * After a staff alert is stored, tell that admin's open panel tabs to
 * reload the bell so it appears immediately rather than on the next poll
 * (ai/modules/will-call-notifications, phase 3).
 */
class RefreshAdminBell
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel === 'database'
            && $event->notifiable instanceof Admin
            && $event->notification instanceof AppNotification
            && NotificationChannels::broadcasting()) {
            DatabaseNotificationsSent::dispatch($event->notifiable);
        }
    }
}

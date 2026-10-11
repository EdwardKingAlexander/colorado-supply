<?php

namespace App\Support;

use App\Models\Admin;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Sends a staff alert to every admin. Each admin's muted types/channels are
 * applied per recipient by AppNotification::via(); portal users never
 * receive admin alerts (ai/modules/will-call-notifications, phase 2).
 */
class AdminNotifier
{
    public static function send(AppNotification $notification): void
    {
        if (! $notification->type()->isForAdmins()) {
            throw new InvalidArgumentException("{$notification->type()->value} is not an admin notification.");
        }

        $admins = Admin::query()->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }
}

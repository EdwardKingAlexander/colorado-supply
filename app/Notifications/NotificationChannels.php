<?php

namespace App\Notifications;

use App\Models\Admin;
use App\Models\AdminNotificationPreference;
use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Decides the Laravel channels for one notification to one recipient:
 * the type's defaults, minus channels the recipient can't receive, minus
 * channels an admin muted (ai/modules/will-call-notifications-complete, phase 2).
 */
class NotificationChannels
{
    /**
     * @return list<string> Laravel channel names, in canonical order
     */
    public static function for(object $notifiable, NotificationType $type): array
    {
        if (! self::isAudience($notifiable, $type)) {
            return [];
        }

        $muted = $notifiable instanceof Admin
            ? AdminNotificationPreference::mutedChannels($notifiable, $type)
            : [];

        $channels = [];

        foreach (NotificationChannel::cases() as $channel) {
            if ($type->allows($channel)
                && ! in_array($channel, $muted, true)
                && self::canReceive($notifiable, $channel)) {
                array_push($channels, ...self::laravelChannels($channel));
            }
        }

        return $channels;
    }

    /**
     * Admin types reach admins only; buyer types reach portal users and
     * guest (routed email) recipients only.
     */
    public static function isAudience(object $notifiable, NotificationType $type): bool
    {
        return $type->isForAdmins()
            ? $notifiable instanceof Admin
            : $notifiable instanceof User || $notifiable instanceof AnonymousNotifiable;
    }

    public static function canReceive(object $notifiable, NotificationChannel $channel): bool
    {
        if (! $channel->isReady()) {
            return false;
        }

        return match ($channel) {
            NotificationChannel::Email => $notifiable instanceof AnonymousNotifiable
                ? filled($notifiable->routes['mail'] ?? null)
                : filled($notifiable->email ?? null),
            NotificationChannel::Bell => $notifiable instanceof User || $notifiable instanceof Admin,
            // Only people who turned push on for at least one device.
            NotificationChannel::Push => ($notifiable instanceof User || $notifiable instanceof Admin)
                && $notifiable->pushSubscriptions()->exists(),
            // Verified, consented and not opted out (STOP).
            NotificationChannel::Sms => ($notifiable instanceof User || $notifiable instanceof Admin)
                && $notifiable->routeNotificationForTwilio() !== null,
        };
    }

    /**
     * @return list<string>
     */
    private static function laravelChannels(NotificationChannel $channel): array
    {
        return match ($channel) {
            NotificationChannel::Email => ['mail'],
            NotificationChannel::Bell => self::broadcasting() ? ['database', 'broadcast'] : ['database'],
            NotificationChannel::Push => [WebPushChannel::class],
            NotificationChannel::Sms => [TwilioChannel::class],
        };
    }

    /**
     * Live bell updates need a real broadcaster (Reverb). With `log`/`null`
     * (tests, rollback) the bell still works by loading and polling.
     */
    public static function broadcasting(): bool
    {
        return in_array(config('broadcasting.default'), ['reverb', 'pusher', 'ably'], true);
    }
}

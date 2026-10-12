<?php

namespace App\Notifications;

/**
 * The delivery channels people choose between (ai/modules/will-call-notifications).
 * Case order is the canonical delivery order.
 */
enum NotificationChannel: string
{
    case Email = 'email';
    case Bell = 'bell';
    case Push = 'push';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Bell => 'Bell',
            self::Push => 'Push',
            self::Sms => 'Text (SMS)',
        };
    }

    /**
     * Whether this channel can deliver anything on this install. Push needs
     * VAPID keys; SMS is wired in phase 6.
     */
    public function isReady(): bool
    {
        return match ($this) {
            self::Email, self::Bell => true,
            self::Push => filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key')),
            self::Sms => false,
        };
    }
}

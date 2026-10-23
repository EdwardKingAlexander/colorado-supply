<?php

namespace App\Models;

use App\Notifications\NotificationChannel;
use App\Notifications\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An admin's choice for one notification type on one channel. No row means
 * the channel is on (ai/modules/will-call-notifications-complete, phase 2).
 */
class AdminNotificationPreference extends Model
{
    protected $fillable = ['admin_id', 'type', 'channel', 'enabled'];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'channel' => NotificationChannel::class,
            'enabled' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Channels this admin turned off for a type.
     *
     * @return list<NotificationChannel>
     */
    public static function mutedChannels(Admin $admin, NotificationType $type): array
    {
        return self::query()
            ->where('admin_id', $admin->getKey())
            ->where('type', $type)
            ->where('enabled', false)
            ->pluck('channel')
            ->all();
    }

    /**
     * Every muted type/channel pair for an admin, as "type:channel" keys.
     *
     * @return list<string>
     */
    public static function mutedKeys(Admin $admin): array
    {
        return self::query()
            ->where('admin_id', $admin->getKey())
            ->where('enabled', false)
            ->get()
            ->map(fn (self $preference) => "{$preference->type->value}:{$preference->channel->value}")
            ->all();
    }
}

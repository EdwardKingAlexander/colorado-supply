<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use NotificationChannels\WebPush\PushSubscription;

/**
 * Saving, listing and removing a person's push devices, shared by the buyer
 * endpoints and the admin My notifications page
 * (ai/modules/will-call-notifications, phase 5).
 */
class PushDevices
{
    /**
     * Validate a browser PushSubscription (JSON form) and return
     * {endpoint, key, token, encoding}.
     *
     * @return array{endpoint: string, key: string, token: string, encoding: string}
     */
    public static function validate(array $input): array
    {
        $data = Validator::make($input, [
            // Push services are always https; anything else is not a browser subscription.
            'endpoint' => ['required', 'string', 'max:500', 'url:https'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ])->validate();

        return [
            'endpoint' => $data['endpoint'],
            'key' => $data['keys']['p256dh'],
            'token' => $data['keys']['auth'],
            'encoding' => $data['content_encoding'] ?? 'aes128gcm',
        ];
    }

    /**
     * @param  array{endpoint: string, key: string, token: string, encoding: string}  $subscription
     */
    public static function save(User|Admin $owner, array $subscription): PushSubscription
    {
        return $owner->updatePushSubscription(
            $subscription['endpoint'],
            $subscription['key'],
            $subscription['token'],
            $subscription['encoding'],
        );
    }

    public static function remove(User|Admin $owner, ?int $id = null, ?string $endpoint = null): void
    {
        $owner->pushSubscriptions()
            ->when($id, fn ($query) => $query->whereKey($id))
            ->when(! $id && $endpoint, fn ($query) => $query->where('endpoint', $endpoint))
            ->when(! $id && ! $endpoint, fn ($query) => $query->whereRaw('1 = 0'))
            ->delete();
    }

    /**
     * @return list<array{id: int, label: string, endpoint: string, added: ?string}>
     */
    public static function list(User|Admin $owner): array
    {
        return $owner->pushSubscriptions()
            ->latest()
            ->get()
            ->map(fn (PushSubscription $subscription) => [
                'id' => $subscription->id,
                'label' => self::label($subscription->endpoint),
                'endpoint' => $subscription->endpoint,
                'added' => $subscription->created_at?->format('M j, Y'),
            ])
            ->all();
    }

    /**
     * A recognisable name from the push service: browsers don't share more.
     */
    public static function label(string $endpoint): string
    {
        $host = (string) parse_url($endpoint, PHP_URL_HOST);

        return match (true) {
            str_ends_with($host, 'push.apple.com') => 'Safari / iPhone / iPad',
            str_ends_with($host, 'googleapis.com'), str_ends_with($host, '.google.com') => 'Chrome / Android',
            str_ends_with($host, 'mozilla.com') => 'Firefox',
            str_ends_with($host, 'notify.windows.com') => 'Edge / Windows',
            default => 'Browser',
        };
    }
}

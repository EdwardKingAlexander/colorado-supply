<?php

use App\Filament\Pages\MyNotifications;
use App\Models\Admin;
use App\Models\AdminNotificationPreference;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NotificationChannel;
use App\Notifications\NotificationChannels;
use App\Notifications\NotificationType;
use App\Notifications\OrderStatusUpdated;
use App\Support\PushDevices;
use Filament\Facades\Filament;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\WebPushChannel;

function browserSubscription(string $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => ['p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM', 'auth' => 'tBHItJI5svbpez7KI4CCXg'],
        'content_encoding' => 'aes128gcm',
    ];
}

beforeEach(function () {
    // Deterministic keys for the test, whatever the local .env holds.
    config([
        'webpush.vapid.public_key' => 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U',
        'webpush.vapid.private_key' => 'UUxI4O8-FbRouAevSmBQ6o18hgE4nSG3qwvJTfKc-ls',
    ]);
    $this->user = User::factory()->create();
});

describe('buyer devices', function () {
    it('saves, lists and removes this browser', function () {
        $this->actingAs($this->user)
            ->postJson(route('push-subscriptions.store'), browserSubscription())
            ->assertCreated()
            ->assertJsonPath('devices.0.label', 'Chrome / Android');

        expect($this->user->pushSubscriptions()->count())->toBe(1);

        $this->actingAs($this->user)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => browserSubscription()['endpoint']])
            ->assertOk()
            ->assertJsonCount(0, 'devices');
    });

    it('rejects anything that is not a browser push subscription', function (array $payload, string $field) {
        $this->actingAs($this->user)
            ->postJson(route('push-subscriptions.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'http endpoint' => [['endpoint' => 'http://example.test/push'] + browserSubscription(), 'endpoint'],
        'missing keys' => [['endpoint' => 'https://fcm.googleapis.com/x'], 'keys.p256dh'],
        'bad encoding' => [['content_encoding' => 'gzip'] + browserSubscription(), 'content_encoding'],
    ]);

    it('only lets people remove their own devices', function () {
        $other = User::factory()->create();
        $theirs = PushDevices::save($other, PushDevices::validate(browserSubscription('https://updates.push.services.mozilla.com/wpush/v2/xyz')));

        $this->actingAs($this->user)->deleteJson(route('push-subscriptions.destroy'), ['id' => $theirs->id])->assertOk();
        $this->actingAs($this->user)->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $theirs->endpoint])->assertOk();

        expect(PushSubscription::query()->whereKey($theirs->id)->exists())->toBeTrue();
    });

    it('moves a shared device to whoever turned push on last', function () {
        $previous = User::factory()->create();
        PushDevices::save($previous, PushDevices::validate(browserSubscription()));

        $this->actingAs($this->user)->postJson(route('push-subscriptions.store'), browserSubscription())->assertCreated();

        expect($previous->pushSubscriptions()->count())->toBe(0)
            ->and($this->user->pushSubscriptions()->count())->toBe(1);
    });

    it('requires login', function () {
        $this->postJson(route('push-subscriptions.store'), browserSubscription())->assertUnauthorized();
    });

    it('shares the public key with the store only when push is configured', function () {
        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page->where('notificationBell.pushKey', config('webpush.vapid.public_key')));

        config(['webpush.vapid.private_key' => null]);

        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page->where('notificationBell.pushKey', null));
    });
});

describe('delivery', function () {
    it('pushes only to people with a device turned on', function () {
        expect(NotificationChannels::for($this->user, NotificationType::OrderShipped))->not->toContain(WebPushChannel::class);

        PushDevices::save($this->user, PushDevices::validate(browserSubscription()));

        expect(NotificationChannels::for($this->user, NotificationType::OrderShipped))->toBe(['mail', 'database', WebPushChannel::class])
            // Payment receipts are email and bell only.
            ->and(NotificationChannels::for($this->user, NotificationType::PaymentReceived))->toBe(['mail', 'database']);
    });

    it('does not push when the server has no VAPID keys', function () {
        PushDevices::save($this->user, PushDevices::validate(browserSubscription()));
        config(['webpush.vapid.public_key' => null]);

        expect(NotificationChannels::for($this->user, NotificationType::OrderShipped))->toBe(['mail', 'database']);
    });

    it('respects an admin muting push for a type', function () {
        $admin = Admin::factory()->create();
        PushDevices::save($admin, PushDevices::validate(browserSubscription()));

        expect(NotificationChannels::for($admin, NotificationType::AdminNewOrder))->toContain(WebPushChannel::class);

        AdminNotificationPreference::create(['admin_id' => $admin->id, 'type' => NotificationType::AdminNewOrder, 'channel' => NotificationChannel::Push]);

        expect(NotificationChannels::for($admin, NotificationType::AdminNewOrder))->toBe(['mail', 'database']);
    });

    it('builds a push with the title, body and a link back to the site', function () {
        $order = Order::factory()->create(['portal_user_id' => $this->user->id]);
        $notification = new OrderStatusUpdated($order, 'shipped', ['carrier' => 'UPS']);
        $notification->id = 'abc';

        $message = $notification->toWebPush($this->user, $notification)->toArray();

        expect($message['title'])->toBe('Your order has shipped')
            ->and($message['body'])->toContain('Carrier: UPS')
            ->and($message['tag'])->toBe('order_shipped:abc')
            ->and($message['data']['url'])->toBe($notification->url($this->user));
    });
});

describe('admin devices', function () {
    beforeEach(function () {
        Filament::setCurrentPanel('admin');
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
    });

    it('turns push on and off from My notifications', function () {
        Livewire::test(MyNotifications::class)
            ->assertSee('Push on this device')
            ->assertFormFieldIsEnabled('admin_new_will_call_order__push')
            ->call('savePushSubscription', browserSubscription())
            ->assertNotified('Notifications turned on for this device');

        expect($this->admin->pushSubscriptions()->count())->toBe(1);

        $id = $this->admin->pushSubscriptions()->value('id');
        Livewire::test(MyNotifications::class)
            ->assertSee('Chrome / Android')
            ->call('removePushDevice', $id);

        expect($this->admin->pushSubscriptions()->count())->toBe(0);
    });

    it('hides the device section when push is not configured', function () {
        config(['webpush.vapid.public_key' => null]);

        Livewire::test(MyNotifications::class)->assertDontSee('Push on this device');
    });
});

it('links the web app manifest so iPhones can install the site for push', function () {
    $this->actingAs($this->user)->get(route('notifications.index'))
        ->assertSee('rel="manifest"', false)
        ->assertSee('apple-touch-icon', false);
});

it('names devices by their push service', function (string $endpoint, string $label) {
    expect(PushDevices::label($endpoint))->toBe($label);
})->with([
    ['https://fcm.googleapis.com/fcm/send/a', 'Chrome / Android'],
    ['https://jmt17.google.com/fcm/send/a', 'Chrome / Android'],
    ['https://web.push.apple.com/a', 'Safari / iPhone / iPad'],
    ['https://updates.push.services.mozilla.com/wpush/v2/a', 'Firefox'],
    ['https://wns2-par02p.notify.windows.com/w/?token=a', 'Edge / Windows'],
    ['https://push.example.test/a', 'Browser'],
]);

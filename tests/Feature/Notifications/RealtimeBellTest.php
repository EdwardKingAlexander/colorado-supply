<?php

use App\Listeners\RefreshAdminBell;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\NotificationChannels;
use App\Notifications\NotificationType;
use App\Notifications\OrderStatusUpdated;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Switch the app to a Reverb broadcaster with throwaway credentials and
 * re-register the channels on it. Nothing connects to a server: channel
 * authorization only signs locally.
 */
function useReverbForTest(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => '1',
        'broadcasting.connections.reverb.options.host' => 'reverb.example.test',
        'broadcasting.connections.reverb.options.port' => 8080,
        'broadcasting.connections.reverb.options.scheme' => 'https',
    ]);
    app(BroadcastManager::class)->forgetDrivers();
    require base_path('routes/channels.php');
}

function authorizeChannel($test, string $channel)
{
    return $test->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-{$channel}"]);
}

function bellAlert(): AppNotification
{
    return new class extends AppNotification
    {
        public function type(): NotificationType
        {
            return NotificationType::AdminNewWillCallOrder;
        }

        public function title(object $notifiable): string
        {
            return 'New will call order';
        }

        public function body(object $notifiable): string
        {
            return 'Order 123 is waiting to be pulled.';
        }

        public function url(object $notifiable): ?string
        {
            return 'https://example.test/admin/orders/1';
        }
    };
}

describe('channel authorization', function () {
    beforeEach(fn () => useReverbForTest());

    it('lets a portal user listen to their own channel only', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $admin = Admin::factory()->create();

        $this->actingAs($user);

        authorizeChannel($this, "App.Models.User.{$user->id}")->assertOk()->assertJsonStructure(['auth']);
        authorizeChannel($this, "App.Models.User.{$other->id}")->assertForbidden();
        authorizeChannel($this, "App.Models.Admin.{$admin->id}")->assertForbidden();
    });

    it('lets an admin listen to their own admin channel only', function () {
        $admin = Admin::factory()->create();
        $otherAdmin = Admin::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin');

        authorizeChannel($this, "App.Models.Admin.{$admin->id}")->assertOk();
        authorizeChannel($this, "App.Models.Admin.{$otherAdmin->id}")->assertForbidden();
        authorizeChannel($this, "App.Models.User.{$user->id}")->assertForbidden();
    });

    it('refuses guests', function () {
        authorizeChannel($this, 'App.Models.User.1')->assertForbidden();
    });

    it('allows the Reverb websocket in the content security policy', function () {
        expect($this->get('/')->headers->get('Content-Security-Policy'))
            ->toContain('connect-src')
            ->toContain('wss://reverb.example.test:8080');
    });
});

it('adds the live channel only when a real broadcaster is configured', function () {
    $user = User::factory()->create();

    expect(NotificationChannels::for($user, NotificationType::OrderShipped))->toBe(['mail', 'database']);

    config(['broadcasting.default' => 'reverb']);

    expect(NotificationChannels::for($user, NotificationType::OrderShipped))->toBe(['mail', 'database', 'broadcast']);
});

it('stores admin alerts in Filament format with a View link', function () {
    $admin = Admin::factory()->create();

    $admin->notifyNow(bellAlert(), ['database']);

    $data = $admin->notifications()->first()->data;

    expect($data['format'])->toBe('filament')
        ->and($data['title'])->toBe('New will call order')
        ->and($data['body'])->toBe('Order 123 is waiting to be pulled.')
        ->and($data['type'])->toBe('admin_new_will_call_order')
        ->and($data['actions'][0]['url'])->toBe('https://example.test/admin/orders/1');
});

it('broadcasts admin alerts as Filament toasts and buyer updates by type', function () {
    $admin = Admin::factory()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    expect(bellAlert()->toBroadcast($admin)->data['format'])->toBe('filament');

    $buyerUpdate = new OrderStatusUpdated($order, 'shipped');

    expect($buyerUpdate->broadcastType())->toBe('order_shipped')
        ->and($buyerUpdate->toBroadcast($user)->data)->toMatchArray([
            'title' => 'Your order has shipped',
            'type' => 'order_shipped',
        ]);
});

it('tells the admin panel to reload the bell after storing an alert', function () {
    Event::fake([DatabaseNotificationsSent::class]);
    $admin = Admin::factory()->create();
    $listener = new RefreshAdminBell;

    $listener->handle(new NotificationSent($admin, bellAlert(), 'database'));
    Event::assertNotDispatched(DatabaseNotificationsSent::class);

    config(['broadcasting.default' => 'reverb']);
    $listener->handle(new NotificationSent($admin, bellAlert(), 'mail'));
    Event::assertNotDispatched(DatabaseNotificationsSent::class);

    $listener->handle(new NotificationSent($admin, bellAlert(), 'database'));
    Event::assertDispatched(DatabaseNotificationsSent::class);
});

describe('buyer notifications endpoints', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->order = Order::factory()->create(['portal_user_id' => $this->user->id]);
        foreach (['shipped', 'delivered'] as $transition) {
            $this->user->notifyNow(new OrderStatusUpdated($this->order, $transition), ['database']);
        }
        $this->notification = $this->user->notifications()->latest()->first();
    });

    it('lists notifications with the shared fields', function () {
        $this->actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('next_page', null)
            ->assertJsonStructure(['notifications' => [['id', 'type', 'title', 'body', 'url', 'icon', 'read_at', 'created_human']]]);
    });

    it('pages 15 at a time and filters unread', function () {
        foreach (range(1, 15) as $i) {
            $this->user->notifyNow(new OrderStatusUpdated($this->order, 'fulfilled'), ['database']);
        }
        $this->user->notifications()->latest()->first()->markAsRead();

        $this->actingAs($this->user)->getJson(route('notifications.index'))
            ->assertJsonCount(15, 'notifications')->assertJsonPath('next_page', 2);
        $this->actingAs($this->user)->getJson(route('notifications.index', ['page' => 2]))
            ->assertJsonCount(2, 'notifications');
        $this->actingAs($this->user)->getJson(route('notifications.index', ['filter' => 'unread']))
            ->assertJsonPath('unread_count', 16)->assertJsonPath('next_page', 2);
    });

    it('marks one read, unread, and deletes it', function () {
        $id = $this->notification->id;

        $this->actingAs($this->user)->postJson(route('notifications.mark-read', $id))->assertJsonPath('unread_count', 1);
        expect($this->notification->fresh()->read_at)->not->toBeNull();

        $this->actingAs($this->user)->postJson(route('notifications.mark-unread', $id))->assertJsonPath('unread_count', 2);

        $this->actingAs($this->user)->deleteJson(route('notifications.destroy', $id))->assertJsonPath('unread_count', 1);
        expect($this->notification->fresh())->toBeNull();
    });

    it("can't touch someone else's notifications", function () {
        $intruder = User::factory()->create();
        $id = $this->notification->id;

        $this->actingAs($intruder)->postJson(route('notifications.mark-read', $id))->assertNotFound();
        $this->actingAs($intruder)->deleteJson(route('notifications.destroy', $id))->assertNotFound();
        $this->actingAs($intruder)->postJson(route('notifications.mark-read', Str::uuid()))->assertNotFound();

        expect($this->notification->fresh())->not->toBeNull()->and($this->notification->fresh()->read_at)->toBeNull();
    });

    it('renders the notifications page', function () {
        $this->actingAs($this->user)
            ->get(route('notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Notifications/Index')
                ->where('filter', 'unread')
                ->has('notifications', 2)
                ->where('unread_count', 2));
    });

    it('shows rows saved before the shared payload', function () {
        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => OrderStatusUpdated::class,
            'data' => ['order_number' => 'SO-1', 'label' => 'Old style update', 'tracker_url' => 'https://example.test/track'],
            'created_at' => now()->addMinute(),
        ]);

        $this->actingAs($this->user)->getJson(route('notifications.index'))
            ->assertJsonPath('notifications.0.title', 'Old style update')
            ->assertJsonPath('notifications.0.url', 'https://example.test/track');
    });

    it('shares the unread count with portal users only', function () {
        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page->where('notificationBell.unread', 2)->where('notificationBell.live', false));
    });
});

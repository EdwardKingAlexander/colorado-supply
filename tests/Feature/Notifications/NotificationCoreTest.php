<?php

use App\Models\Admin;
use App\Models\AdminNotificationPreference;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Notifications\NotificationChannel;
use App\Notifications\NotificationChannels;
use App\Notifications\NotificationType;
use App\Notifications\OrderPaymentFailed;
use App\Notifications\OrderStatusUpdated;
use App\Support\AdminNotifier;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function testAlert(NotificationType $type = NotificationType::AdminNewWillCallOrder): AppNotification
{
    return new class($type) extends AppNotification
    {
        public function __construct(private NotificationType $alertType) {}

        public function type(): NotificationType
        {
            return $this->alertType;
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
            return '/admin/orders/1';
        }
    };
}

it('declares a label, description, icon and channels for every type', function (NotificationType $type) {
    expect($type->label())->not->toBeEmpty()
        ->and($type->description())->not->toBeEmpty()
        ->and($type->icon())->toStartWith('heroicon-o-')
        ->and($type->defaultChannels())->not->toBeEmpty()
        ->and($type->allows(NotificationChannel::Bell))->toBeTrue();
})->with(NotificationType::cases());

it('limits text messages to the moments that matter', function () {
    $sms = array_filter(NotificationType::cases(), fn (NotificationType $type) => $type->allows(NotificationChannel::Sms));

    expect(array_values($sms))->toBe([
        NotificationType::ReadyForPickup,
        NotificationType::PickupReminder,
        NotificationType::AdminNewWillCallOrder,
    ]);
});

it('never routes admin alerts to portal users or buyer updates to admins', function () {
    $user = User::factory()->create();
    $admin = Admin::factory()->create();

    expect(NotificationChannels::for($user, NotificationType::AdminNewOrder))->toBe([])
        ->and(NotificationChannels::for($admin, NotificationType::OrderShipped))->toBe([])
        ->and(NotificationChannels::for($admin, NotificationType::AdminNewOrder))->toBe(['mail', 'database'])
        ->and(NotificationChannels::for($user, NotificationType::OrderShipped))->toBe(['mail', 'database']);
});

it('sends guests email only', function () {
    $guest = Notification::route('mail', 'guest@example.com');

    expect(NotificationChannels::for($guest, NotificationType::OrderShipped))->toBe(['mail'])
        ->and(NotificationChannels::for(new AnonymousNotifiable, NotificationType::OrderShipped))->toBe([]);
});

it('alerts every admin on their unmuted channels', function () {
    Notification::fake();
    [$everything, $noEmail, $muted] = Admin::factory()->count(3)->create();
    $type = NotificationType::AdminNewWillCallOrder;

    AdminNotificationPreference::create(['admin_id' => $noEmail->id, 'type' => $type, 'channel' => NotificationChannel::Email]);
    foreach ([NotificationChannel::Email, NotificationChannel::Bell] as $channel) {
        AdminNotificationPreference::create(['admin_id' => $muted->id, 'type' => $type, 'channel' => $channel]);
    }

    AdminNotifier::send($alert = testAlert($type));

    // The fake matches by exact class; the test alert is an anonymous subclass.
    Notification::assertSentTo($everything, get_class($alert), fn ($notification, array $channels) => $channels === ['mail', 'database']);
    Notification::assertSentTo($noEmail, get_class($alert), fn ($notification, array $channels) => $channels === ['database']);
    Notification::assertNotSentTo($muted, get_class($alert));
});

it('applies a mute to that type only', function () {
    Notification::fake();
    $admin = Admin::factory()->create();
    AdminNotificationPreference::create([
        'admin_id' => $admin->id, 'type' => NotificationType::AdminNewOrder, 'channel' => NotificationChannel::Email,
    ]);

    AdminNotifier::send($alert = testAlert(NotificationType::AdminQuoteRequested));

    Notification::assertSentTo($admin, get_class($alert), fn ($notification, array $channels) => $channels === ['mail', 'database']);
});

it('sends admin alerts to no portal user', function () {
    Notification::fake();
    $user = User::factory()->create();
    Admin::factory()->create();

    AdminNotifier::send($alert = testAlert());

    Notification::assertNotSentTo($user, get_class($alert));
});

it('refuses to broadcast a buyer notification to admins', function () {
    AdminNotifier::send(testAlert(NotificationType::OrderShipped));
})->throws(InvalidArgumentException::class);

it('stores the shared payload in the bell', function () {
    $admin = Admin::factory()->create();

    $admin->notifyNow(testAlert(), ['database']);

    expect($admin->notifications()->first()->data)->toBe([
        'type' => 'admin_new_will_call_order',
        'title' => 'New will call order',
        'body' => 'Order 123 is waiting to be pulled.',
        'url' => '/admin/orders/1',
        'icon' => 'heroicon-o-building-storefront',
        'label' => 'New will call order',
    ]);
});

it('keeps the original order bell keys alongside the shared payload', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    $user->notifyNow(new OrderStatusUpdated($order, 'shipped', ['carrier' => 'UPS']), ['database']);

    $data = $user->notifications()->first()->data;

    expect($data)->toHaveKeys(['order_id', 'order_number', 'transition', 'tracker_url', 'timestamp', 'label'])
        ->and($data['type'])->toBe('order_shipped')
        ->and($data['title'])->toBe('Your order has shipped')
        ->and($data['body'])->toContain('Carrier: UPS')
        ->and($data['url'])->toBe($data['tracker_url']);
});

it('words a payment failure without doubled punctuation', function () {
    $order = Order::factory()->create();

    expect((new OrderPaymentFailed($order, 'Card declined.'))->body(new AnonymousNotifiable))
        ->toBe('Reason: Card declined. Please try again or use a different payment method.');
});

it('prunes old bell notifications', function () {
    $user = User::factory()->create();
    $insert = fn (int $daysAgo, bool $read) => DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => '{}',
        'read_at' => $read ? now()->subDays($daysAgo) : null,
        'created_at' => now()->subDays($daysAgo),
        'updated_at' => now()->subDays($daysAgo),
    ]);

    $insert(100, true);   // pruned
    $insert(30, true);    // kept
    $insert(100, false);  // kept: unread keeps 180 days
    $insert(200, false);  // pruned

    $this->artisan('notifications:prune')->assertSuccessful();

    expect(DB::table('notifications')->count())->toBe(2);
});

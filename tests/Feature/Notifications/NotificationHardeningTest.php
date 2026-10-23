<?php

use App\Models\Order;
use App\Models\User;
use App\Notifications\ReadyForPickupNotification;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;
use NotificationChannels\WebPush\ReportHandler;
use NotificationChannels\WebPush\WebPushChannel;

// Phase 7 hardening (ai/modules/will-call-notifications-complete).

function seedNotifications(User $user, int $count): void
{
    foreach (range(1, $count) as $i) {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ReadyForPickupNotification::class,
            'data' => ['type' => 'ready_for_pickup', 'title' => "Alert {$i}", 'body' => 'Body', 'url' => null, 'icon' => 'heroicon-o-bell'],
            'read_at' => $i % 2 ? now() : null,
        ]);
    }
}

function notificationIndexQueries(User $user): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->actingAs($user)->getJson(route('notifications.index'))->assertOk();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

it('loads the bell list with the same number of queries however many notifications there are', function () {
    $few = User::factory()->create();
    $many = User::factory()->create();
    seedNotifications($few, 2);
    seedNotifications($many, 20);

    // Warm up anything cached once per request cycle (config, permissions).
    notificationIndexQueries($few);

    expect(notificationIndexQueries($many))->toBe(notificationIndexQueries($few));
});

it('queues each channel separately, with limited retries and backoff', function () {
    Queue::fake();
    $user = User::factory()->create();

    $user->notify(new ReadyForPickupNotification(Order::factory()->create()));

    Queue::assertPushed(SendQueuedNotifications::class, 2);
    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->tries === 3 && $job->backoff() === [60, 300]);
    expect(Queue::pushed(SendQueuedNotifications::class)->map(fn ($job) => $job->channels[0])->all())
        ->toEqualCanonicalizing(['mail', 'database']);
});

it('forgets a device whose push service says the subscription is gone', function () {
    $user = User::factory()->create();
    $endpoint = 'https://fcm.googleapis.com/fcm/send/gone';
    $user->updatePushSubscription($endpoint, 'BPublicKey', 'authToken');

    $webPush = Mockery::mock(WebPush::class);
    $webPush->shouldReceive('queueNotification')->once();
    $webPush->shouldReceive('flush')->once()->andReturnUsing(function () use ($endpoint) {
        yield new MessageSentReport(new Request('POST', $endpoint), new Response(410), false, 'Gone');
    });

    (new WebPushChannel($webPush, app(ReportHandler::class)))
        ->send($user, new ReadyForPickupNotification(Order::factory()->create()));

    expect($user->pushSubscriptions()->count())->toBe(0);
});

it('rate limits every notification, push and text endpoint', function (string $name) {
    expect(collect(Route::getRoutes()->getByName($name)->gatherMiddleware())->contains(fn ($middleware) => str_starts_with($middleware, 'throttle:')))->toBeTrue();
})->with([
    'notifications.read', 'notifications.mark-read', 'notifications.mark-unread', 'notifications.destroy',
    'push-subscriptions.store', 'push-subscriptions.destroy',
    'sms-subscription.store', 'sms-subscription.verify', 'sms-subscription.destroy',
    'webhooks.twilio.sms',
]);

<?php

use App\Enums\FulfillmentMethod;
use App\Enums\PickupStatus;
use App\Filament\Pages\MyNotifications;
use App\Models\Admin;
use App\Models\AdminNotificationPreference;
use App\Models\Location;
use App\Models\Order;
use App\Models\SmsRecipient;
use App\Models\User;
use App\Notifications\Admin\NewOrderAlert;
use App\Notifications\NotificationChannel;
use App\Notifications\NotificationChannels;
use App\Notifications\NotificationType;
use App\Notifications\ReadyForPickupNotification;
use App\Notifications\SmsVerificationCode;
use App\Support\SmsQuietHours;
use App\Support\SmsSubscriptions;
use Filament\Facades\Filament;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use NotificationChannels\Twilio\TwilioChannel;
use Twilio\Security\RequestValidator;

function enableSms(): void
{
    config([
        'sms.enabled' => true,
        'twilio-notification-channel.account_sid' => 'ACtest',
        'twilio-notification-channel.auth_token' => 'test-token',
        'twilio-notification-channel.from' => '+13035550199',
    ]);
}

function verifiedRecipient(User|Admin $owner, string $phone = '+13035550100'): SmsRecipient
{
    return $owner->smsRecipient()->create([
        'phone_e164' => $phone, 'consented_at' => now(), 'consent_text_version' => '2026-10-12', 'verified_at' => now(),
    ]);
}

function sentCode(): string
{
    $code = null;
    Notification::assertSentOnDemand(SmsVerificationCode::class, function (SmsVerificationCode $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    return $code;
}

beforeEach(function () {
    Notification::fake();
    $this->user = User::factory()->create();
});

describe('switched off (the default until Twilio approval)', function () {
    it('never texts, even a verified number', function () {
        verifiedRecipient($this->user);

        expect(NotificationChannel::Sms->isReady())->toBeFalse()
            ->and(NotificationChannels::for($this->user, NotificationType::ReadyForPickup))->not->toContain(TwilioChannel::class);
    });

    it('hides the sign-up and refuses it', function () {
        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page->where('notificationBell.smsConsent', null));

        $this->actingAs($this->user)->postJson(route('sms-subscription.store'), ['phone' => '3035550100', 'consent' => true])->assertNotFound();
        Notification::assertNothingSent();
    });
});

describe('sign-up', function () {
    beforeEach(fn () => enableSms());

    it('requires a US mobile number and an explicit consent tick', function () {
        $this->actingAs($this->user)->postJson(route('sms-subscription.store'), ['phone' => '555-0100', 'consent' => true])
            ->assertJsonValidationErrors(['phone' => '10-digit']);
        $this->actingAs($this->user)->postJson(route('sms-subscription.store'), ['phone' => '3035550100'])
            ->assertJsonValidationErrors('consent');

        expect(SmsRecipient::count())->toBe(0);
        Notification::assertNothingSent();
    });

    it('records consent, texts a code, and turns on after the right code', function () {
        $this->actingAs($this->user)
            ->postJson(route('sms-subscription.store'), ['phone' => '(303) 555-0100', 'consent' => true])
            ->assertOk()
            ->assertJson(['state' => 'pending', 'phone' => '(303) 555-0100']);

        $recipient = $this->user->smsRecipient;
        expect($recipient->phone_e164)->toBe('+13035550100')
            ->and($recipient->consent_text_version)->toBe(config('sms.consent_version'))
            ->and($recipient->consent_ip)->not->toBeNull()
            ->and($recipient->canReceive())->toBeFalse();

        Notification::assertSentOnDemand(SmsVerificationCode::class, fn ($n, $channels, AnonymousNotifiable $to) => $to->routes['twilio'] === '+13035550100');

        $this->actingAs($this->user)->postJson(route('sms-subscription.verify'), ['code' => '000000'])
            ->assertJsonValidationErrors(['code' => 'not right']);
        $this->actingAs($this->user)->postJson(route('sms-subscription.verify'), ['code' => sentCode()])
            ->assertOk()->assertJson(['state' => 'on']);

        expect($this->user->fresh()->routeNotificationForTwilio())->toBe('+13035550100');
    });

    it('expires codes and limits guesses and sends', function () {
        SmsSubscriptions::start($this->user, '3035550100', true, '127.0.0.1');
        $code = sentCode();

        foreach (range(1, 5) as $i) {
            try {
                SmsSubscriptions::verify($this->user->fresh(), '111111');
            } catch (ValidationException) {
            }
        }
        expect(fn () => SmsSubscriptions::verify($this->user->fresh(), $code))->toThrow(ValidationException::class, 'Too many tries');

        SmsSubscriptions::start($this->user->fresh(), '3035550100', true, null);
        Carbon::setTestNow(now()->addMinutes(11));
        expect(fn () => SmsSubscriptions::verify($this->user->fresh(), '123456'))->toThrow(ValidationException::class, 'expired');
        Carbon::setTestNow();

        SmsSubscriptions::start($this->user->fresh(), '3035550100', true, null);
        expect(fn () => SmsSubscriptions::start($this->user->fresh(), '3035550100', true, null))
            ->toThrow(ValidationException::class, 'Too many codes');
    });

    it('stops texts', function () {
        verifiedRecipient($this->user);

        $this->actingAs($this->user)->deleteJson(route('sms-subscription.destroy'))->assertJson(['state' => 'off']);

        expect($this->user->fresh()->routeNotificationForTwilio())->toBeNull();
    });

    afterEach(fn () => RateLimiter::clear('sms-verify:'.$this->user->getMorphClass().':'.$this->user->id));
});

describe('delivery', function () {
    beforeEach(fn () => enableSms());

    it('texts only the alerts that allow it, to verified numbers that have not opted out', function () {
        expect(NotificationChannels::for($this->user, NotificationType::ReadyForPickup))->not->toContain(TwilioChannel::class);

        $recipient = verifiedRecipient($this->user);
        $this->user->refresh();

        expect(NotificationChannels::for($this->user, NotificationType::ReadyForPickup))->toBe(['mail', 'database', TwilioChannel::class])
            ->and(NotificationChannels::for($this->user, NotificationType::OrderShipped))->not->toContain(TwilioChannel::class);

        $recipient->update(['opted_out_at' => now()]);
        $this->user->refresh();

        expect(NotificationChannels::for($this->user, NotificationType::ReadyForPickup))->not->toContain(TwilioChannel::class);
    });

    it('lets an admin mute texts per alert type', function () {
        $admin = Admin::factory()->create();
        verifiedRecipient($admin);

        expect(NotificationChannels::for($admin->fresh(), NotificationType::AdminNewWillCallOrder))->toContain(TwilioChannel::class);

        AdminNotificationPreference::create(['admin_id' => $admin->id, 'type' => NotificationType::AdminNewWillCallOrder, 'channel' => NotificationChannel::Sms]);

        expect(NotificationChannels::for($admin->fresh(), NotificationType::AdminNewWillCallOrder))->not->toContain(TwilioChannel::class);
    });

    it('writes short, branded, plain-text messages with the opt-out line', function () {
        $order = Order::factory()->create([
            'company_name' => 'Acme Fab & Weld — Front Range Division Incorporated',
            'fulfillment_method' => FulfillmentMethod::WillCall,
            'pickup_location_id' => Location::factory()->pickup()->create(['name' => 'Front Range Will Call'])->id,
            'pickup_status' => PickupStatus::ReadyForPickup,
            'pickup_code' => 'WC-7KQ4',
        ]);

        $ready = (new ReadyForPickupNotification($order))->toTwilio($this->user)->content;
        $alert = (new NewOrderAlert($order))->toTwilio(Admin::factory()->create())->content;

        expect($ready)->toBe("Colorado Supply: Order {$order->order_number} is ready at Front Range Will Call. Pickup code WC-7KQ4. Reply STOP to opt out.");

        foreach ([$ready, $alert] as $text) {
            expect(strlen($text))->toBeLessThanOrEqual(160)
                ->and($text)->toMatch('/^[\x20-\x7E]+$/')
                ->and($text)->toStartWith('Colorado Supply: ')
                ->and($text)->toEndWith('Reply STOP to opt out.');
        }
    });

    it('holds texts overnight and sends everything else right away', function () {
        $order = Order::factory()->create();
        $notification = new ReadyForPickupNotification($order);

        Carbon::setTestNow(Carbon::parse('2026-10-12 12:00', 'America/Denver'));
        expect($notification->withDelay($this->user))->toBe([]);

        Carbon::setTestNow(Carbon::parse('2026-10-12 22:30', 'America/Denver'));
        expect($notification->withDelay($this->user)[TwilioChannel::class]->toDateTimeString())->toBe('2026-10-13 08:00:00');

        Carbon::setTestNow(Carbon::parse('2026-10-13 06:15', 'America/Denver'));
        expect(SmsQuietHours::sendAt()->toDateTimeString())->toBe('2026-10-13 08:00:00');

        Carbon::setTestNow();
    });
});

describe('replies webhook', function () {
    beforeEach(function () {
        enableSms();
        $this->recipient = verifiedRecipient($this->user);
    });

    $reply = function ($test, string $body, ?string $signature = null) {
        $url = route('webhooks.twilio.sms');
        $params = ['From' => '+13035550100', 'Body' => $body, 'MessageSid' => 'SM1'];
        $signature ??= (new RequestValidator('test-token'))->computeSignature($url, $params);

        // Sent like Twilio: a raw form-encoded body (the controller verifies the raw parameters).
        return $test->call('POST', $url, [], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_X_TWILIO_SIGNATURE' => $signature,
        ], http_build_query($params));
    };

    it('refuses requests not signed by Twilio', function () use ($reply) {
        $reply($this, 'STOP', 'forged')->assertForbidden();

        expect($this->recipient->fresh()->opted_out_at)->toBeNull();
    });

    it('opts out on STOP and back in on START', function () use ($reply) {
        $reply($this, ' stop please')->assertOk()->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        expect($this->recipient->fresh()->opted_out_at)->not->toBeNull();

        $reply($this, 'START')->assertOk();
        expect($this->recipient->fresh()->opted_out_at)->toBeNull();
    });

    it('answers HELP', function () use ($reply) {
        $reply($this, 'HELP')->assertOk()->assertSee('<Message>Colorado Supply &amp; Procurement order alerts.', false);
    });
});

it('shows the admin text sign-up and runs it end to end', function () {
    enableSms();
    Filament::setCurrentPanel('admin');
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(MyNotifications::class)
        ->assertSee('Text alerts')
        ->set('smsPhone', '303-555-0101')
        ->set('smsConsent', true)
        ->call('startSms')
        ->assertHasNoErrors()
        ->assertSee('We texted a 6-digit code')
        ->set('smsCode', sentCode())
        ->call('verifySms')
        ->assertHasNoErrors()
        ->assertSee('On for (303) 555-0101');

    expect($admin->fresh()->routeNotificationForTwilio())->toBe('+13035550101');
    RateLimiter::clear('sms-verify:'.$admin->getMorphClass().':'.$admin->id);
});

it('publishes the text message terms', function () {
    $this->get(route('sms-terms'))
        ->assertOk()
        ->assertSee('Reply')
        ->assertSee('STOP')
        ->assertSee('do not sell, rent or share mobile numbers', false);
});

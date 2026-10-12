<?php

use App\Filament\Pages\MyNotifications;
use App\Models\Admin;
use App\Models\AdminNotificationPreference;
use App\Notifications\NotificationChannel;
use App\Notifications\NotificationType;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->admin = Admin::factory()->create();
    $this->actingAs($this->admin, 'admin');
});

it('starts with every live channel on', function () {
    config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

    Livewire::test(MyNotifications::class)
        ->assertOk()
        ->assertSee('New will call order')
        ->assertFormSet([
            'admin_new_will_call_order__email' => true,
            'admin_new_will_call_order__bell' => true,
            'admin_payment_received__bell' => true,
        ])
        ->assertFormFieldIsDisabled('admin_new_will_call_order__push')
        ->assertFormFieldIsDisabled('admin_new_will_call_order__sms');
});

it('saves muted channels for this admin only and clears them when turned back on', function () {
    $other = Admin::factory()->create();

    Livewire::test(MyNotifications::class)
        ->fillForm(['admin_new_order__email' => false])
        ->call('save')
        ->assertNotified('Notification choices saved');

    expect(AdminNotificationPreference::mutedChannels($this->admin, NotificationType::AdminNewOrder))
        ->toBe([NotificationChannel::Email])
        ->and(AdminNotificationPreference::mutedChannels($other, NotificationType::AdminNewOrder))->toBe([]);

    Livewire::test(MyNotifications::class)
        ->assertFormSet(['admin_new_order__email' => false])
        ->fillForm(['admin_new_order__email' => true])
        ->call('save');

    expect(AdminNotificationPreference::query()->count())->toBe(0);
});

it('turns off email everywhere in one click', function () {
    Livewire::test(MyNotifications::class)
        ->callAction('muteAllEmail')
        ->assertFormSet(['admin_new_will_call_order__email' => false, 'admin_new_will_call_order__bell' => true])
        ->call('save');

    $emailTypes = array_filter(NotificationType::forAdmins(), fn (NotificationType $type) => $type->allows(NotificationChannel::Email));

    expect(AdminNotificationPreference::query()->where('channel', 'email')->count())->toBe(count($emailTypes))
        ->and(AdminNotificationPreference::query()->where('channel', 'bell')->count())->toBe(0);
});

it('is linked from the admin user menu', function () {
    expect(Filament::getPanel('admin')->getUserMenuItems())->toHaveKey('myNotifications');
});

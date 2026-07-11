<?php

use App\Models\Admin;
use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use App\Support\EmailVerificationSettings;
use Illuminate\Support\Facades\Notification;

test('registration sends a verification notification and leaves the email unverified', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'enforce@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::firstWhere('email', 'enforce@example.com');

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmailAddress::class);
});

test('an unverified user is redirected to the verification notice from gated routes', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('sam.favorites'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('a verified user passes verification-gated routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('sam.favorites'))
        ->assertOk();
});

test('an admin is never subject to customer email verification', function () {
    $admin = Admin::factory()->create();

    // Admins hitting /dashboard are intentionally forwarded to the Filament
    // panel (DashboardController) — the assertion here is that they are NOT
    // bounced to the customer verification notice.
    $this->actingAs($admin, 'admin')
        ->get(route('dashboard'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

test('gating is bypassed while the setting is disabled', function () {
    EmailVerificationSettings::setEnabled(false);

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('sam.favorites'))
        ->assertOk();
});

test('no verification notification is sent while the setting is disabled', function () {
    Notification::fake();
    EmailVerificationSettings::setEnabled(false);

    $this->post('/register', [
        'name' => 'Muted User',
        'email' => 'muted@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Notification::assertNothingSent();
});

test('the verification notice and resend routes stay reachable for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();
});

test('profile remains accessible to unverified users so they can fix a typoed email', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();
});

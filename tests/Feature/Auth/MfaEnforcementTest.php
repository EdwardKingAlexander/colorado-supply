<?php

use App\Console\Commands\ResetCustomerMultiFactorAuthentication;
use App\Models\Admin;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationService;
use PragmaRX\Google2FA\Google2FA;

function enrolledUser(): User
{
    $user = User::factory()->create([
        'two_factor_method' => 'totp',
        'two_factor_confirmed_at' => now(),
    ]);
    $user->two_factor_secret = app(Google2FA::class)->generateSecretKey();
    $user->two_factor_recovery_codes = app(TwoFactorAuthenticationService::class)->generateRecoveryCodes();
    $user->save();

    return $user;
}

it('does not force enrollment while MFA is optional (flag off)', function () {
    config(['mfa.required' => false]);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('forces an unenrolled user to enroll when MFA is required', function () {
    config(['mfa.required' => true]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('profile.edit'));
});

it('keeps the enrollment surface reachable under forced enrollment', function () {
    config(['mfa.required' => true]);
    $user = User::factory()->create();

    // Profile page (enrollment target) and the enable action must not loop.
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    $this->actingAs($user)
        ->post(route('mfa.enable'), ['method' => 'totp'])
        ->assertRedirect(route('profile.edit'));
    // Logout stays reachable too.
    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
});

it('still challenges an enrolled user when MFA is required', function () {
    config(['mfa.required' => true]);
    $user = enrolledUser();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('mfa.challenge'));
});

it('never forces enrollment on an admin', function () {
    config(['mfa.required' => true]);
    $admin = Admin::factory()->create();

    // Passes the gate and is handed to the admin panel, not to customer enrollment.
    $this->actingAs($admin, 'admin')
        ->get(route('dashboard'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('forbids disabling MFA while it is required', function () {
    config(['mfa.required' => true]);
    $user = enrolledUser();

    $this->withSession(['mfa.passed' => true])
        ->actingAs($user)
        ->delete(route('mfa.disable'), ['password' => 'password'])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('resets a locked-out user via the artisan command', function () {
    $user = enrolledUser();

    $this->artisan(ResetCustomerMultiFactorAuthentication::class, ['email' => $user->email])
        ->expectsConfirmation(
            "This will disable all multi-factor authentication for {$user->email}, requiring them to re-enroll on next login. Continue?",
            'yes'
        )
        ->assertExitCode(0);

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('errors cleanly when resetting an unknown email', function () {
    $this->artisan(ResetCustomerMultiFactorAuthentication::class, ['email' => 'nobody@example.com'])
        ->assertExitCode(1);
});

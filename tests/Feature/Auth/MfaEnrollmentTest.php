<?php

use App\Models\User;
use App\Notifications\MfaEmailCode;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;

it('enrolls a user with an authenticator app', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('mfa.enable'), ['method' => 'totp']);

    $secret = session('mfa.pending_secret');
    expect($secret)->not->toBeNull();

    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->actingAs($user)
        ->post(route('mfa.confirm'), ['code' => $code])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeTrue()
        ->and($user->two_factor_method)->toBe('totp')
        ->and($user->two_factor_secret)->toBe($secret)
        ->and($user->two_factor_recovery_codes)->toHaveCount(8);
});

it('rejects an invalid authenticator code and does not enroll', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('mfa.enable'), ['method' => 'totp']);

    $this->actingAs($user)
        ->post(route('mfa.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('enrolls a user with email codes', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('mfa.enable'), ['method' => 'email']);

    $code = null;
    Notification::assertSentTo($user, MfaEmailCode::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $this->actingAs($user)
        ->post(route('mfa.confirm'), ['code' => $code])
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeTrue()
        ->and($user->two_factor_method)->toBe('email')
        ->and($user->two_factor_secret)->toBeNull();
});

it('regenerates recovery codes for an enrolled user', function () {
    $user = User::factory()->create([
        'two_factor_method' => 'email',
        'two_factor_confirmed_at' => now(),
    ]);
    $user->two_factor_recovery_codes = ['OLD01-OLD02'];
    $user->save();

    $this->withSession(['mfa.passed' => true])
        ->actingAs($user)
        ->post(route('mfa.recovery-codes'))
        ->assertSessionHas('mfaRecoveryCodes');

    expect($user->fresh()->two_factor_recovery_codes)
        ->not->toContain('OLD01-OLD02')
        ->toHaveCount(8);
});

it('disables MFA with the correct password', function () {
    $user = User::factory()->create([
        'two_factor_method' => 'totp',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->withSession(['mfa.passed' => true])
        ->actingAs($user)
        ->delete(route('mfa.disable'), ['password' => 'password'])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('rejects disabling MFA with a wrong password', function () {
    $user = User::factory()->create([
        'two_factor_method' => 'totp',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->withSession(['mfa.passed' => true])
        ->actingAs($user)
        ->delete(route('mfa.disable'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('blocks guests from MFA management routes', function () {
    $this->post(route('mfa.enable'), ['method' => 'totp'])->assertRedirect(route('login'));
    $this->post(route('mfa.confirm'), ['code' => '123456'])->assertRedirect(route('login'));
    $this->delete(route('mfa.disable'))->assertRedirect(route('login'));
});

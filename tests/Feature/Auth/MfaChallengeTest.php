<?php

use App\Models\Admin;
use App\Models\User;
use App\Notifications\MfaEmailCode;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;

/**
 * Create an enrolled TOTP user and return [user, secret].
 */
function enrolledTotpUser(): array
{
    $secret = app(Google2FA::class)->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_method' => 'totp',
        'two_factor_confirmed_at' => now(),
    ]);
    $user->two_factor_secret = $secret;
    $user->two_factor_recovery_codes = app(TwoFactorAuthenticationService::class)->generateRecoveryCodes();
    $user->save();

    return [$user, $secret];
}

it('redirects an enrolled user to the challenge after password login', function () {
    [$user, $secret] = enrolledTotpUser();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('mfa.challenge'));

    // Not yet passed: the dashboard bounces back to the challenge.
    $this->get(route('dashboard'))->assertRedirect(route('mfa.challenge'));
});

it('reaches the dashboard after a correct TOTP code', function () {
    [$user, $secret] = enrolledTotpUser();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->post(route('mfa.challenge.verify'), ['code' => $code])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertOk();
});

it('rejects a wrong code and keeps the user blocked', function () {
    [$user] = enrolledTotpUser();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('mfa.challenge.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->get(route('dashboard'))->assertRedirect(route('mfa.challenge'));
});

it('accepts a recovery code and consumes it', function () {
    [$user] = enrolledTotpUser();
    $recoveryCode = $user->two_factor_recovery_codes[0];

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('mfa.challenge.verify'), ['recovery_code' => $recoveryCode])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->two_factor_recovery_codes)->not->toContain($recoveryCode);
});

it('never accepts an email code from an authenticator-app user', function () {
    Notification::fake();
    [$user] = enrolledTotpUser();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    // They cannot request one…
    $this->post(route('mfa.challenge.email'))->assertForbidden();
    Notification::assertNothingSent();

    // …and a valid email code planted directly is still rejected.
    $code = app(TwoFactorAuthenticationService::class)->issueEmailCode($user, 'challenge');

    $this->post(route('mfa.challenge.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');

    $this->get(route('dashboard'))->assertRedirect(route('mfa.challenge'));
});

it('lets a legacy email-code user in once, then holds them on authenticator setup', function () {
    Notification::fake();
    $user = User::factory()->create([
        'two_factor_method' => 'email',
        'two_factor_confirmed_at' => now(),
    ]);
    $user->two_factor_recovery_codes = ['AAAAA-BBBBB'];
    $user->save();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('mfa.challenge.email'))->assertSessionHasNoErrors();

    $code = null;
    Notification::assertSentTo($user, MfaEmailCode::class, function ($n) use (&$code) {
        $code = $n->code;

        return true;
    });

    $this->post(route('mfa.challenge.verify'), ['code' => $code])
        ->assertRedirect(route('dashboard'));

    // Past the challenge, every page except setup sends them to set up an
    // authenticator app; JSON callers get 423.
    $this->get(route('dashboard'))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'mfa-authenticator-required');
    $this->getJson(route('dashboard'))->assertStatus(423);
    $this->get(route('profile.edit'))->assertOk();

    // Switching to the authenticator app releases them.
    $this->post(route('mfa.enable'), ['method' => 'totp']);
    $totp = app(Google2FA::class)->getCurrentOtp(session('mfa.pending_secret'));
    $this->post(route('mfa.confirm'), ['code' => $totp])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->two_factor_method)->toBe('totp')
        ->and($user->usesLegacyEmailTwoFactor())->toBeFalse()
        ->and($user->mfaCodes()->count())->toBe(0);

    $this->get(route('dashboard'))->assertOk();
});

it('lets a legacy email-code user pass with a recovery code', function () {
    $user = User::factory()->create([
        'two_factor_method' => 'email',
        'two_factor_confirmed_at' => now(),
    ]);
    $user->two_factor_recovery_codes = ['AAAAA-BBBBB'];
    $user->save();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('mfa.challenge.verify'), ['recovery_code' => 'AAAAA-BBBBB'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertRedirect(route('profile.edit'));
});

it('logs an unenrolled user straight in without a challenge', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertOk();
});

it('blocks disabling MFA before the challenge is passed', function () {
    [$user] = enrolledTotpUser();

    // Logged in (password only) but has not cleared the challenge: the disable
    // route must not run — otherwise a password-only attacker could remove MFA.
    $this->actingAs($user)
        ->delete(route('mfa.disable'), ['password' => 'password'])
        ->assertRedirect(route('mfa.challenge'));

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('does not challenge an admin on the customer surface', function () {
    $admin = Admin::factory()->create();

    // The admin passes the MFA gate (Filament MFA already covered them) and is
    // handed off to the admin panel — never bounced to the customer challenge.
    $this->actingAs($admin, 'admin')
        ->get(route('dashboard'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

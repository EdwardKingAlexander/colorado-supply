<?php

use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TwoFactorAuthenticationService::class);
    $this->google2fa = app(Google2FA::class);
});

it('generates a usable base32 TOTP secret', function () {
    $secret = $this->service->generateSecret();

    expect($secret)->toBeString()->not->toBeEmpty()
        ->and($secret)->toMatch('/^[A-Z2-7]+$/'); // RFC 4648 base32 alphabet
});

it('verifies a valid TOTP code and rejects a wrong one', function () {
    $secret = $this->service->generateSecret();
    $validCode = $this->google2fa->getCurrentOtp($secret);

    expect($this->service->verifyTotp($secret, $validCode))->toBeTrue()
        ->and($this->service->verifyTotp($secret, '000000'))->toBeFalse();
});

it('generates the requested number of unique recovery codes', function () {
    $codes = $this->service->generateRecoveryCodes(8);

    expect($codes)->toHaveCount(8)
        ->and(array_unique($codes))->toHaveCount(8);
});

it('consumes a recovery code once and rejects reuse', function () {
    $user = User::factory()->create();
    $codes = $this->service->generateRecoveryCodes();
    $user->two_factor_recovery_codes = $codes;
    $user->save();

    $target = $codes[0];

    expect($this->service->consumeRecoveryCode($user, $target))->toBeTrue()
        ->and($this->service->consumeRecoveryCode($user->fresh(), $target))->toBeFalse();

    expect($user->fresh()->two_factor_recovery_codes)->not->toContain($target);
});

it('issues, verifies, and single-uses an email code', function () {
    $user = User::factory()->create();

    $code = $this->service->issueEmailCode($user, 'challenge');

    expect($this->service->verifyEmailCode($user, $code, 'challenge'))->toBeTrue()
        // second use fails (consumed)
        ->and($this->service->verifyEmailCode($user, $code, 'challenge'))->toBeFalse();
});

it('rejects an email code presented for the wrong purpose', function () {
    $user = User::factory()->create();

    $code = $this->service->issueEmailCode($user, 'enroll');

    expect($this->service->verifyEmailCode($user, $code, 'challenge'))->toBeFalse();
});

it('rejects an expired email code', function () {
    $user = User::factory()->create();

    $code = $this->service->issueEmailCode($user, 'challenge');
    $user->mfaCodes()->latest()->first()->update(['expires_at' => now()->subMinute()]);

    expect($this->service->verifyEmailCode($user, $code, 'challenge'))->toBeFalse();
});

it('rate-limits email code issuance', function () {
    $user = User::factory()->create();

    expect($this->service->canIssueEmailCode($user))->toBeTrue();

    for ($i = 0; $i < 5; $i++) {
        $this->service->issueEmailCode($user, 'challenge');
    }

    expect($this->service->canIssueEmailCode($user))->toBeFalse();

    RateLimiter::clear('mfa-email-code:'.$user->id);
    expect($this->service->canIssueEmailCode($user))->toBeTrue();
});

it('stores the TOTP secret and recovery codes encrypted at rest', function () {
    $user = User::factory()->create();

    $user->two_factor_secret = 'PLAINTEXTSECRET123';
    $user->two_factor_recovery_codes = ['AAAAA-BBBBB'];
    $user->save();

    $raw = DB::table('users')->where('id', $user->id)->first();

    expect($raw->two_factor_secret)->not->toContain('PLAINTEXTSECRET123')
        ->and($raw->two_factor_recovery_codes)->not->toContain('AAAAA-BBBBB');

    // ...but the casts decrypt transparently on read.
    expect($user->fresh()->two_factor_secret)->toBe('PLAINTEXTSECRET123')
        ->and($user->fresh()->two_factor_recovery_codes)->toBe(['AAAAA-BBBBB']);
});

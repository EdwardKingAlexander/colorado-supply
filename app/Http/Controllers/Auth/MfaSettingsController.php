<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class MfaSettingsController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactor) {}

    /**
     * Begin authenticator-app enrollment. The TOTP secret is held pending in
     * the session and only persisted once confirmed. Email codes are not
     * offered: NIST SP 800-63B forbids email as an authenticator.
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'method' => ['required', 'in:totp'],
        ]);

        $request->session()->put('mfa.pending_secret', $this->twoFactor->generateSecret());
        $request->session()->put('mfa.pending_method', 'totp');

        return Redirect::route('profile.edit');
    }

    /**
     * Confirm enrollment with a live code, persist the factor, and issue
     * recovery codes (shown once via a one-time flash).
     */
    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();
        $secret = $request->session()->get('mfa.pending_secret');

        if ($request->session()->get('mfa.pending_method') !== 'totp' || ! $secret) {
            throw ValidationException::withMessages([
                'code' => 'Start by setting up your authenticator app.',
            ]);
        }

        if (! $this->twoFactor->verifyTotp($secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'The code is invalid. Please try again.',
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        // Also moves a legacy email-method customer onto the authenticator app.
        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_method = 'totp';
        $user->two_factor_confirmed_at = now();
        $user->save();

        $user->mfaCodes()->delete();

        $request->session()->forget(['mfa.pending_secret', 'mfa.pending_method']);

        return Redirect::route('profile.edit')->with('mfaRecoveryCodes', $recoveryCodes);
    }

    /**
     * Regenerate recovery codes for an already-enrolled user.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasTwoFactorEnabled(), 403);

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return Redirect::route('profile.edit')->with('mfaRecoveryCodes', $recoveryCodes);
    }

    /**
     * Disable MFA. Requires password confirmation. (Phase 4 restricts this once
     * MFA is mandatory for web users.)
     */
    public function disable(Request $request): RedirectResponse
    {
        // When MFA is mandatory, disabling is not permitted — the user would be
        // bounced straight back to forced enrollment anyway.
        if (config('mfa.required')) {
            throw ValidationException::withMessages([
                'password' => 'Two-factor authentication is required and cannot be disabled.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_method = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $user->mfaCodes()->delete();

        return Redirect::route('profile.edit')->with('status', 'mfa-disabled');
    }
}

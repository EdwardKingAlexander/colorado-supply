<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\MfaEmailCode;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MfaChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly TwoFactorAuthenticationService $twoFactor) {}

    public function show(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasTwoFactorEnabled(), 403);

        return Inertia::render('Auth/Mfa/Challenge', [
            'method' => $user->two_factor_method,
            'status' => session('status'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        abort_unless($user && $user->hasTwoFactorEnabled(), 403);

        $this->ensureNotRateLimited($request);

        $passed = false;

        if ($request->filled('recovery_code')) {
            $passed = $this->twoFactor->consumeRecoveryCode($user, (string) $request->input('recovery_code'));
        } elseif ($request->filled('code')) {
            $code = (string) $request->input('code');

            if ($user->two_factor_method === 'totp' && $user->two_factor_secret) {
                $passed = $this->twoFactor->verifyTotp($user->two_factor_secret, $code);
            }

            // Email codes work as the configured method or as a fallback.
            if (! $passed) {
                $passed = $this->twoFactor->verifyEmailCode($user, $code, 'challenge');
            }
        }

        if (! $passed) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'code' => 'The code is invalid or has expired. Please try again.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->put('mfa.passed', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function sendEmailCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasTwoFactorEnabled(), 403);

        if (! $this->twoFactor->canIssueEmailCode($user)) {
            throw ValidationException::withMessages([
                'code' => 'Too many code requests. Please wait a few minutes and try again.',
            ]);
        }

        $code = $this->twoFactor->issueEmailCode($user, 'challenge');
        $user->notify(new MfaEmailCode($code));

        return back()->with('status', 'A verification code has been emailed to you.');
    }

    private function ensureNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'code' => "Too many attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'mfa-challenge:'.$request->user()->id.'|'.$request->ip();
    }
}

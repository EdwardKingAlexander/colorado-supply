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

        $passed = $this->twoFactor->verifyChallenge(
            $user,
            $request->input('code'),
            $request->input('recovery_code'),
        );

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

    /**
     * Email a challenge code — only for customers still on the legacy email
     * method, so they can get in once and switch to an authenticator app.
     */
    public function sendEmailCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->usesLegacyEmailTwoFactor(), 403);

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

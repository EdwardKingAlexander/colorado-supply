<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the customer surface behind a completed MFA challenge.
 *
 * - Admins pass through (they satisfied MFA via the Filament panel).
 * - Enrolled web users must clear the login challenge (`mfa.passed`).
 * - Unenrolled web users pass through when MFA is opt-in, or are redirected to
 *   enrollment when `config('mfa.required')` is on.
 */
class EnsureMfaSatisfied
{
    public function handle(Request $request, Closure $next): Response
    {
        // Admin sessions already satisfied MFA via the Filament panel; never
        // double-challenge on shared (auth.web_or_admin) routes.
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if (! $user) {
            return $next($request);
        }

        // Always let the user reach the challenge itself and log out.
        if ($request->routeIs('mfa.challenge*', 'logout')) {
            return $next($request);
        }

        if (! $user->hasTwoFactorEnabled()) {
            // Mandatory mode: force enrollment before anything else, but keep
            // the enrollment surface itself reachable to avoid a redirect loop.
            if (config('mfa.required') && ! $this->isEnrollmentSurface($request)) {
                if ($request->expectsJson()) {
                    abort(423, 'Two-factor authentication setup is required.');
                }

                return redirect()->route('profile.edit')->with('status', 'mfa-setup-required');
            }

            return $next($request);
        }

        if (! $request->session()->get('mfa.passed', false)) {
            if ($request->expectsJson()) {
                abort(423, 'Two-factor authentication required.');
            }

            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }

    /**
     * Routes an unenrolled user must still reach to complete forced enrollment
     * (view the profile page, start/confirm a factor, verify email, log out).
     */
    private function isEnrollmentSurface(Request $request): bool
    {
        return $request->routeIs(
            'profile.edit',
            'profile.update',
            'mfa.enable',
            'mfa.confirm',
            'verification.notice',
            'verification.send',
            'verification.verify',
        );
    }
}

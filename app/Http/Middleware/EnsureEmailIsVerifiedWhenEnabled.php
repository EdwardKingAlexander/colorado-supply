<?php

namespace App\Http\Middleware;

use App\Support\EmailVerificationSettings;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Same contract as the framework's `verified` middleware, but a no-op while
 * the email-verification setting is disabled (development convenience), and
 * only ever enforced against web-guard users — admins authenticate via the
 * `admin` guard, which does not implement MustVerifyEmail.
 */
class EnsureEmailIsVerifiedWhenEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user instanceof MustVerifyEmail
            || $user->hasVerifiedEmail()
            || ! EmailVerificationSettings::isEnabled()
        ) {
            return $next($request);
        }

        return $request->expectsJson()
            ? abort(403, 'Your email address is not verified.')
            : redirect()->guest(route('verification.notice'));
    }
}

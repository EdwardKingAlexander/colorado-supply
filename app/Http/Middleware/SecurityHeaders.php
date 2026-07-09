<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('csp_nonce', bin2hex(random_bytes(16)));

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        $csp = $this->contentSecurityPolicy($request);

        if ($this->shouldEnforceContentSecurityPolicy($request)) {
            $response->headers->set('Content-Security-Policy', $csp);
        } else {
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function contentSecurityPolicy(Request $request): string
    {
        $nonce = $request->attributes->get('csp_nonce');
        $nonceSource = $nonce ? " 'nonce-{$nonce}'" : '';
        $scriptSrc = "'self'{$nonceSource} https://www.googletagmanager.com https://www.google.com https://www.gstatic.com";
        $connectSrc = "'self' https://www.google-analytics.com https://www.google.com https://www.gstatic.com";

        // Vite's dev server needs eval (HMR module reload) and a websocket
        // connection to itself; built assets served by PHP/Apache do not.
        if ($this->usesViteDevServer()) {
            $scriptSrc .= " 'unsafe-eval'";
            $connectSrc .= ' ws: wss: http://localhost:* http://127.0.0.1:*';
        }

        $directives = [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https:",
            "connect-src {$connectSrc}",
            'frame-src https://www.google.com',
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $directives);
    }

    protected function shouldEnforceContentSecurityPolicy(Request $request): bool
    {
        return ! $request->is(
            'admin',
            'admin/*',
            'livewire/*',
            'filament/*',
            'api/*'
        );
    }

    protected function usesViteDevServer(): bool
    {
        return is_file(public_path('hot'));
    }
}

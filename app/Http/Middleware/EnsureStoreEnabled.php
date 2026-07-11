<?php

namespace App\Http\Middleware;

use App\Support\McpSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get store enabled setting (default to true if not set)
        $settings = McpSettings::for('store-settings', ['enabled' => true]);
        $storeEnabled = $settings['enabled'] ?? true;

        // If store is enabled, allow access
        if ($storeEnabled) {
            return $next($request);
        }

        // Store is disabled - check if user is an admin
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        // Store is disabled for a non-admin: render a branded "temporarily
        // unavailable" page instead of a bare 403. Kept at HTTP 200 so Inertia
        // client-side navigation renders it as a page rather than an error modal.
        return Inertia::render('Store/Unavailable', [
            'contactEmail' => 'Edward@cogovsupply.com',
        ])->toResponse($request);
    }
}

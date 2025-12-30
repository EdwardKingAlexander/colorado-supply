<?php

namespace App\Http\Middleware;

use App\Support\McpSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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

        // Store is disabled and user is not an admin
        abort(403, 'The store is currently unavailable. Please check back later.');
    }
}

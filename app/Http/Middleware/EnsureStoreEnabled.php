<?php

namespace App\Http\Middleware;

use App\Support\StoreAvailability;
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
        // Open store, or an admin previewing while it's closed: allow access.
        if (StoreAvailability::isEnabled() || Auth::guard('admin')->check()) {
            return $next($request);
        }

        // Store is closed for a non-admin: render the branded "Under
        // Maintenance" page instead of a bare 403. Kept at HTTP 200 so Inertia
        // client-side navigation renders it as a page rather than an error modal.
        return Inertia::render('Store/Unavailable', StoreAvailability::maintenancePageProps())
            ->toResponse($request);
    }
}

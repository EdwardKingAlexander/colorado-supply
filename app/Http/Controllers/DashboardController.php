<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardFilterRequest;
use App\Models\User;
use App\Services\Dashboard\UserDashboardDataService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DashboardController extends Controller
{
    public function __construct(private UserDashboardDataService $dashboard) {}

    public function __invoke(DashboardFilterRequest $request): Response|RedirectResponse|SymfonyResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            // Filament's admin panel is a separate, non-Inertia application.
            // A plain redirect() gets XHR-followed by Inertia's Link component,
            // which then renders Filament's raw HTML inside Inertia's
            // non-Inertia-response debug modal instead of navigating there.
            // Inertia::location() forces a real browser navigation instead.
            return Inertia::location(route('filament.admin.pages.dashboard'));
        }

        return Inertia::render('Dashboard', $this->dashboard->dataFor($user, $request->filters()));
    }
}

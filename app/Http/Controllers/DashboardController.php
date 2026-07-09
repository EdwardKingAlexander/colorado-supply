<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardFilterRequest;
use App\Models\User;
use App\Services\Dashboard\UserDashboardDataService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private UserDashboardDataService $dashboard) {}

    public function __invoke(DashboardFilterRequest $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return Inertia::render('Dashboard', $this->dashboard->dataFor($user, $request->filters()));
    }
}

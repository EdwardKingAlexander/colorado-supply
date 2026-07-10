<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardReportRequest;
use App\Models\User;
use App\Services\Dashboard\UserDashboardReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DashboardReportController extends Controller
{
    public function __construct(private UserDashboardReportService $reports) {}

    public function __invoke(DashboardReportRequest $request): Response|RedirectResponse|SymfonyResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            // See DashboardController for why Inertia::location() is required
            // here instead of a plain redirect() to a non-Inertia route.
            return Inertia::location(route('filament.admin.pages.dashboard'));
        }

        return Inertia::render('Dashboard/Reports', $this->reports->reportFor($user, $request->filters()));
    }
}

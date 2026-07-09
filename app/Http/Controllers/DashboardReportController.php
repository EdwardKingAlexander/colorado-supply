<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardReportRequest;
use App\Models\User;
use App\Services\Dashboard\UserDashboardReportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardReportController extends Controller
{
    public function __construct(private UserDashboardReportService $reports) {}

    public function __invoke(DashboardReportRequest $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return Inertia::render('Dashboard/Reports', $this->reports->reportFor($user, $request->filters()));
    }
}

<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreReportRequest;
use App\Http\Requests\Reports\UpdateReportRequest;
use App\Models\Report;
use App\Models\User;
use App\Reporting\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SavedReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user('web');

        $reports = Report::query()
            ->visibleTo($user)
            ->with('owner:id,name')
            ->latest('updated_at')
            ->get()
            ->map(fn (Report $report) => $this->reports->summary($user, $report));

        return response()->json([
            'data' => [
                'mine' => $reports->where('owner.is_me', true)->values(),
                'shared' => $reports->where('owner.is_me', false)->values(),
            ],
        ]);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $user = $request->user('web');
        $report = $this->reports->create($user, $request->validated());

        return response()->json(['data' => $this->reports->detail($user, $report->load('owner:id,name'))], 201);
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        $user = $request->user('web');
        $this->authorizeVisible($user, $report);

        return response()->json(['data' => $this->reports->detail($user, $report->load('owner:id,name'))]);
    }

    public function update(UpdateReportRequest $request, Report $report): JsonResponse
    {
        $user = $request->user('web');
        $report = $this->reports->update($user, $report, $request->validated());

        return response()->json(['data' => $this->reports->detail($user, $report->load('owner:id,name'))]);
    }

    public function destroy(Request $request, Report $report): JsonResponse
    {
        $user = $request->user('web');
        $this->authorizeVisible($user, $report);
        Gate::forUser($user)->authorize('delete', $report);

        $report->delete();

        return response()->json(['deleted' => true]);
    }

    public function duplicate(Request $request, Report $report): JsonResponse
    {
        $user = $request->user('web');
        $this->authorizeVisible($user, $report);
        $validated = $request->validate(['name' => ['nullable', 'string', 'max:120']]);

        $copy = $this->reports->duplicate($user, $report, $validated['name'] ?? null);

        return response()->json(['data' => $this->reports->detail($user, $copy->load('owner:id,name'))], 201);
    }

    public function run(Request $request, Report $report): JsonResponse
    {
        $user = $request->user('web');
        $this->authorizeVisible($user, $report);

        return response()->json(['data' => $this->reports->run($user, $report)]);
    }

    /**
     * Reports the user can't see answer 404, so ids can't be probed.
     */
    private function authorizeVisible(User $user, Report $report): void
    {
        abort_unless($user->can('view', $report), 404);
    }
}

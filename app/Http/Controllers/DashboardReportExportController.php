<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardReportRequest;
use App\Models\User;
use App\Services\Dashboard\UserDashboardReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardReportExportController extends Controller
{
    public function __construct(private UserDashboardReportService $reports) {}

    public function __invoke(DashboardReportRequest $request): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $report = $this->reports->exportFor($user, $request->filters());
        $filename = 'purchasing-report-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $report['columns']);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, array_map(fn (string $column) => $row[$column] ?? null, $report['columns']));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SamOpportunitiesStateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SamOpportunitiesExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        if (! auth()->guard('admin')->check()) {
            abort(403);
        }

        $data = $this->loadState();

        if (empty($data['opportunities'])) {
            abort(404, 'No SAM opportunities available to export.');
        }

        $filters = $this->extractFilters($request);
        $opportunities = $this->applyFilters($data['opportunities'], $filters);

        if (empty($opportunities)) {
            abort(404, 'No SAM opportunities match the selected filters.');
        }

        set_time_limit(300);

        $timestamp = now()->timezone('America/Denver')->format('Ymd-His');
        $filename = "sam-opportunities-{$timestamp}.xlsx";

        return Excel::download(
            new SamOpportunitiesStateExport($opportunities),
            $filename
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadState(): array
    {
        $path = app_path('Mcp/Servers/Business/State/sam-opportunities.json');

        if (! File::exists($path)) {
            abort(404, 'SAM opportunities state file not found.');
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @return array{q:string, notice:string, naics:string, state:string, set_aside:string}
     */
    protected function extractFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'notice' => trim((string) $request->query('notice', '')),
            'naics' => trim((string) $request->query('naics', '')),
            'state' => trim((string) $request->query('state', '')),
            'set_aside' => trim((string) $request->query('set_aside', '')),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $opportunities
     * @param array{q:string, notice:string, naics:string, state:string, set_aside:string} $filters
     * @return array<int, array<string, mixed>>
     */
    protected function applyFilters(array $opportunities, array $filters): array
    {
        $filtered = array_filter($opportunities, function ($opp) use ($filters) {
            $title = $opp['title'] ?? '';
            $agency = $opp['agency_name'] ?? '';
            $solicitation = $opp['solicitation_number'] ?? '';
            $notice = $opp['notice_type'] ?? '';
            $naics = $opp['naics_code'] ?? '';
            $state = $opp['state_code'] ?? '';
            $setAside = $opp['set_aside_type'] ?? '';

            if ($filters['q'] !== '') {
                $haystack = strtolower($title.' '.$agency.' '.$solicitation);
                if (! str_contains($haystack, strtolower($filters['q']))) {
                    return false;
                }
            }

            if ($filters['notice'] !== '' && strcasecmp($notice, $filters['notice']) !== 0) {
                return false;
            }

            if ($filters['naics'] !== '' && strcasecmp($naics, $filters['naics']) !== 0) {
                return false;
            }

            if ($filters['state'] !== '' && strcasecmp($state, $filters['state']) !== 0) {
                return false;
            }

            if ($filters['set_aside'] !== '' && strcasecmp($setAside, $filters['set_aside']) !== 0) {
                return false;
            }

            return true;
        });

        return array_values($filtered);
    }
}

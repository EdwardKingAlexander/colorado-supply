<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\SamOpportunitiesExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SamOpportunityExportController extends Controller
{
    /**
     * Export SAM opportunities to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'favorites_only' => 'sometimes|boolean',
            'filters' => 'sometimes|array',
            'filters.agency' => 'sometimes|string',
            'filters.notice_type' => 'sometimes|string',
            'filters.naics_code' => 'sometimes|string',
        ]);

        $user = $request->user();
        $favoritesOnly = $validated['favorites_only'] ?? false;
        $filters = $validated['filters'] ?? [];

        // Increase time limit for large exports
        set_time_limit(300); // 5 minutes

        // Generate filename with timestamp
        $timestamp = now()->timezone('America/Denver')->format('Ymd-His');
        $filename = "sam-opportunities-{$timestamp}.xlsx";

        try {
            // Create and download export
            return Excel::download(
                new SamOpportunitiesExport($user, $favoritesOnly, $filters),
                $filename
            );
        } catch (\Exception $e) {
            // Log the error for debugging
            logger()->error('SAM Opportunity export failed', [
                'user_id' => $user->id,
                'favorites_only' => $favoritesOnly,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            // Return error response
            abort(500, 'Export failed. Please try again or contact support if the issue persists.');
        }
    }
}

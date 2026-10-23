<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Reporting\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportPreviewController extends Controller
{
    /**
     * Run any definition, saved or not, for the builder's live preview.
     */
    public function __invoke(Request $request, ReportService $reports): JsonResponse
    {
        $request->validate(['definition' => ['required', 'array']]);

        return response()->json(['data' => $reports->preview($request->user('web'), $request->input('definition'))]);
    }
}

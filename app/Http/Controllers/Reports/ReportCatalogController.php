<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Reporting\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportCatalogController extends Controller
{
    public function __invoke(Request $request, Catalog $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->forUser($request->user('web'))]);
    }
}

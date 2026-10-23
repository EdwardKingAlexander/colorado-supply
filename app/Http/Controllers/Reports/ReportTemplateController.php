<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Reporting\Templates\TemplateCatalog;
use Illuminate\Http\JsonResponse;

class ReportTemplateController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => TemplateCatalog::all()]);
    }
}

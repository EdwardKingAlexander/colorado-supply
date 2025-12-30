<?php

namespace App\Http\Controllers;

use App\Exports\MilSpecPartsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MilSpecPartsController extends Controller
{
    /**
     * Download Excel file containing mil-spec parts database.
     */
    public function downloadExcel(): BinaryFileResponse
    {
        return Excel::download(
            new MilSpecPartsExport,
            'mil-spec-parts-database.xlsx'
        );
    }
}

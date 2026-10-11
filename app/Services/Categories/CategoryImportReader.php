<?php

namespace App\Services\Categories;

use App\Imports\CategoryRowsImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reads a .csv/.xlsx file from an absolute path into heading-keyed rows ready
 * for CategoryImportPlanner. Kept separate from the planner so the planner
 * stays pure and unit-testable, and so the file format is swappable.
 */
class CategoryImportReader
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(string $absolutePath): array
    {
        $sheets = Excel::toArray(new CategoryRowsImport, $absolutePath);

        // First sheet only; empty file -> no rows.
        return $sheets[0] ?? [];
    }
}

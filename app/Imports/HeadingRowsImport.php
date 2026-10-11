<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads an uploaded .csv/.xlsx into heading-keyed rows. `WithHeadingRow`
 * normalizes headers to snake_case, so "Manufacturer CAGE" becomes
 * "manufacturer_cage". Rows are pulled with Excel::toArray().
 */
class HeadingRowsImport implements ToArray, WithHeadingRow
{
    public function array(array $array): void
    {
        // Intentionally unused; see Excel::toArray().
    }
}

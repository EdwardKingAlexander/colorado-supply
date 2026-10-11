<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads uploaded category files into heading-keyed rows. `WithHeadingRow`
 * normalizes the first row into snake_case keys (name, parent, description,
 * image), so downstream code sees a stable shape regardless of header casing.
 */
class CategoryRowsImport implements ToArray, WithHeadingRow
{
    public function array(array $array): void
    {
        // Rows are pulled via Excel::toArray(); this method is intentionally unused.
    }
}

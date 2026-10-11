<?php

namespace App\Services\Categories;

/**
 * Outcome of committing a CategoryImportPlan — count summary for the UI
 * notification.
 */
class CategoryImportResult
{
    /**
     * @param  int[]  $createdIds
     */
    public function __construct(
        public readonly int $createdCount,
        public readonly int $reusedCount,
        public readonly array $createdIds = [],
    ) {}
}

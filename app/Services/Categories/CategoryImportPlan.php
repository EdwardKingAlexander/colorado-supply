<?php

namespace App\Services\Categories;

/**
 * The result of planning a category import: the ordered (top-down) list of
 * nodes to persist, plus per-row errors. Produced by CategoryImportPlanner and
 * consumed by CategoryImportCommitter. Contains no database writes.
 */
class CategoryImportPlan
{
    /**
     * @param  CategoryImportNode[]  $nodes  Ordered roots-first for safe creation.
     * @param  array<int, array{row: int, message: string, values: array}>  $errors
     */
    public function __construct(
        public readonly array $nodes,
        public readonly array $errors,
    ) {}

    public function newCount(): int
    {
        return count(array_filter($this->nodes, fn (CategoryImportNode $n) => $n->isCreate()));
    }

    public function reuseCount(): int
    {
        return count(array_filter($this->nodes, fn (CategoryImportNode $n) => $n->isReuse()));
    }

    public function errorCount(): int
    {
        return count($this->errors);
    }

    public function isValid(): bool
    {
        return $this->errorCount() === 0;
    }
}

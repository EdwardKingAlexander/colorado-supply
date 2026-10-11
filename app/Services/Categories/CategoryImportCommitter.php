<?php

namespace App\Services\Categories;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Persists a validated CategoryImportPlan. Creates CREATE nodes in dependency
 * (roots-first) order and reuses REUSE nodes, all inside a single database
 * transaction so any failure rolls the whole import back. Idempotent:
 * re-committing the same plan/CSV creates nothing new.
 */
class CategoryImportCommitter
{
    public function commit(CategoryImportPlan $plan): CategoryImportResult
    {
        if (! $plan->isValid()) {
            throw new RuntimeException('Cannot commit a category import plan that contains errors.');
        }

        return DB::transaction(function () use ($plan): CategoryImportResult {
            $pathToId = [];   // resolved path => category id (both reused and created)
            $createdIds = [];
            $reusedCount = 0;

            foreach ($plan->nodes as $node) {
                $parentId = $this->resolveParentId($node, $pathToId);

                if ($node->isReuse()) {
                    $pathToId[$node->path] = $node->existingId;
                    $reusedCount++;

                    continue;
                }

                $category = Category::create([
                    'parent_id' => $parentId,
                    'name' => $node->name,
                    'slug' => $node->slug,
                    'description' => $node->description,
                    'image' => $node->image,
                ]);

                $pathToId[$node->path] = $category->id;
                $createdIds[] = $category->id;
            }

            return new CategoryImportResult(
                createdCount: count($createdIds),
                reusedCount: $reusedCount,
                createdIds: $createdIds,
            );
        });
    }

    /**
     * @param  array<string, int>  $pathToId
     */
    private function resolveParentId(CategoryImportNode $node, array $pathToId): ?int
    {
        if ($node->parentPath === null) {
            return null; // root
        }

        // Parent that isn't part of the CSV (an existing DB category).
        if ($node->parentExistingId !== null) {
            return $node->parentExistingId;
        }

        // Parent is another node in this plan, persisted earlier (roots-first).
        return $pathToId[$node->parentPath] ?? null;
    }
}

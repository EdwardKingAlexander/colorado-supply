<?php

namespace App\Services\Categories;

use App\Models\Category;
use Illuminate\Support\Str;

/**
 * Turns parsed CSV rows into a validated CategoryImportPlan. Performs NO
 * database writes — it only reads the existing category tree to decide which
 * rows are new (CREATE) vs. already present (REUSE), to generate unique slugs,
 * and to surface per-row errors for a dry-run preview.
 *
 * Rows are associative arrays keyed by normalized header: name (required),
 * parent, description, image. Parent is referenced by the parent's name and
 * resolution is order-independent (parents may appear after their children).
 */
class CategoryImportPlanner
{
    /** @var array<int, array{id:int, parent_id:?int, name:string, slug:string}> */
    private array $existing = [];

    /** @var array<string, int> key "parentId|lowername" => category id */
    private array $existingByParentName = [];

    /** @var array<string, int[]> lowername => [category ids] */
    private array $existingByName = [];

    /** @var array<string, true> slug => true */
    private array $existingSlugs = [];

    /** @var array<string, true> slugs allocated during this plan */
    private array $allocatedSlugs = [];

    /**
     * @param  iterable<array<string, mixed>>  $rows
     */
    public function plan(iterable $rows, int $firstRowNumber = 2): CategoryImportPlan
    {
        $this->loadExisting();
        $this->allocatedSlugs = [];

        $errors = [];
        $entries = [];   // working nodes, mutated during resolution
        $byName = [];    // lowername => [entry indexes] for CSV entries

        // --- Pass 1: read + validate rows ---
        $rowNumber = $firstRowNumber;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $parent = trim((string) ($row['parent'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $image = trim((string) ($row['image'] ?? ''));

            if ($name === '') {
                $errors[] = ['row' => $rowNumber, 'message' => 'Missing required "name".', 'values' => $row];
                $rowNumber++;

                continue;
            }

            $idx = count($entries);
            $entries[$idx] = [
                'row' => $rowNumber,
                'name' => $name,
                'parentName' => $parent,
                'description' => $description !== '' ? $description : null,
                'image' => $image !== '' ? $image : null,
                'parentIdx' => null,        // resolved CSV parent index
                'parentExistingId' => null, // resolved DB parent id
                'path' => null,
                'parentPath' => null,
                'error' => null,
            ];
            $byName[mb_strtolower($name)][] = $idx;
            $rowNumber++;
        }

        // --- Pass 2: resolve each entry's parent (CSV first, then DB) ---
        foreach ($entries as $idx => &$e) {
            if ($e['parentName'] === '') {
                continue; // root
            }

            $key = mb_strtolower($e['parentName']);
            $csvMatches = $byName[$key] ?? [];

            if (count($csvMatches) === 1) {
                $e['parentIdx'] = $csvMatches[0];
            } elseif (count($csvMatches) > 1) {
                $e['error'] = "Ambiguous parent \"{$e['parentName']}\" matches multiple rows in the file.";
            } else {
                $dbMatches = $this->existingByName[$key] ?? [];
                if (count($dbMatches) === 1) {
                    $e['parentExistingId'] = $dbMatches[0];
                } elseif (count($dbMatches) > 1) {
                    $e['error'] = "Ambiguous parent \"{$e['parentName']}\" matches multiple existing categories.";
                } else {
                    $e['error'] = "Unknown parent \"{$e['parentName']}\" — no matching row or existing category.";
                }
            }
        }
        unset($e);

        // --- Pass 3: compute full paths (with cycle detection) ---
        foreach (array_keys($entries) as $idx) {
            $this->resolvePath($idx, $entries, []);
        }

        // --- Pass 4: detect duplicate paths ---
        $seenPath = [];
        foreach ($entries as $idx => &$e) {
            if ($e['error'] !== null || $e['path'] === null) {
                continue;
            }
            if (isset($seenPath[$e['path']])) {
                $firstRow = $entries[$seenPath[$e['path']]]['row'];
                $e['error'] = "Duplicate category (first defined on row {$firstRow}).";
            } else {
                $seenPath[$e['path']] = $idx;
            }
        }
        unset($e);

        // --- Pass 5: collect errors, then decide action + slug top-down ---
        foreach ($entries as $e) {
            if ($e['error'] !== null) {
                $errors[] = [
                    'row' => $e['row'],
                    'message' => $e['error'],
                    'values' => ['name' => $e['name'], 'parent' => $e['parentName']],
                ];
            }
        }

        $valid = array_values(array_filter(
            $entries,
            fn ($e) => $e['error'] === null && $e['path'] !== null,
        ));

        // Parents (shallower paths) must be decided before their children.
        usort($valid, fn ($a, $b) => substr_count($a['path'], '/') <=> substr_count($b['path'], '/'));

        $nodes = [];
        $nodeByPath = [];   // path => node action + existingId, for child resolution
        foreach ($valid as $e) {
            [$parentId, $parentIsNewNode] = $this->resolveParentContext($e, $nodeByPath);

            $lowerName = mb_strtolower($e['name']);
            $matchKey = ($parentId ?? 'null').'|'.$lowerName;

            if (! $parentIsNewNode && isset($this->existingByParentName[$matchKey])) {
                $existingId = $this->existingByParentName[$matchKey];
                $node = new CategoryImportNode(
                    path: $e['path'],
                    name: $e['name'],
                    parentPath: $e['parentPath'],
                    parentExistingId: $e['parentExistingId'],
                    action: CategoryImportNode::REUSE,
                    slug: $this->existing[$existingId]['slug'],
                    description: $e['description'],
                    image: $e['image'],
                    existingId: $existingId,
                    rowNumber: $e['row'],
                );
                $nodeByPath[$e['path']] = ['isNew' => false, 'existingId' => $existingId];
            } else {
                $node = new CategoryImportNode(
                    path: $e['path'],
                    name: $e['name'],
                    parentPath: $e['parentPath'],
                    parentExistingId: $e['parentExistingId'],
                    action: CategoryImportNode::CREATE,
                    slug: $this->generateSlug($e['name']),
                    description: $e['description'],
                    image: $e['image'],
                    existingId: null,
                    rowNumber: $e['row'],
                );
                $nodeByPath[$e['path']] = ['isNew' => true, 'existingId' => null];
            }

            $nodes[] = $node;
        }

        usort($errors, fn ($a, $b) => $a['row'] <=> $b['row']);

        return new CategoryImportPlan($nodes, array_values($errors));
    }

    /**
     * Resolve the parent's persisted id (if any) and whether the parent is a
     * brand-new node in this plan. A new-node parent means the child cannot
     * already exist, so it must be CREATE.
     *
     * @param  array<string, array{isNew:bool, existingId:?int}>  $nodeByPath
     * @return array{0: ?int, 1: bool}
     */
    private function resolveParentContext(array $e, array $nodeByPath): array
    {
        if ($e['parentPath'] === null) {
            return [null, false]; // root
        }

        if ($e['parentExistingId'] !== null) {
            return [$e['parentExistingId'], false];
        }

        // Parent is another CSV node, already decided (processed earlier).
        $parent = $nodeByPath[$e['parentPath']] ?? null;
        if ($parent === null) {
            return [null, true]; // parent unresolved defensively -> treat child as new
        }

        return [$parent['existingId'], $parent['isNew']];
    }

    /**
     * Recursively compute an entry's normalized full path, detecting cycles.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @param  int[]  $visiting
     */
    private function resolvePath(int $idx, array &$entries, array $visiting): ?string
    {
        $e = &$entries[$idx];

        if ($e['error'] !== null) {
            return null;
        }
        if ($e['path'] !== null) {
            return $e['path'];
        }
        if (in_array($idx, $visiting, true)) {
            $e['error'] = "Cycle detected involving \"{$e['name']}\".";

            return null;
        }

        // Root.
        if ($e['parentName'] === '') {
            $e['parentPath'] = null;
            $e['path'] = mb_strtolower($e['name']);

            return $e['path'];
        }

        if ($e['parentIdx'] !== null) {
            $visiting[] = $idx;
            $parentPath = $this->resolvePath($e['parentIdx'], $entries, $visiting);
            if ($parentPath === null) {
                if ($e['error'] === null) {
                    $e['error'] = "Parent \"{$e['parentName']}\" could not be resolved.";
                }

                return null;
            }
            $e['parentPath'] = $parentPath;
            $e['path'] = $parentPath.'/'.mb_strtolower($e['name']);

            return $e['path'];
        }

        if ($e['parentExistingId'] !== null) {
            $parentPath = $this->dbPath($e['parentExistingId']);
            $e['parentPath'] = $parentPath;
            $e['path'] = $parentPath.'/'.mb_strtolower($e['name']);

            return $e['path'];
        }

        return null;
    }

    /**
     * Normalized full path of an existing DB category (walks parent chain).
     */
    private function dbPath(int $id): string
    {
        $parts = [];
        $cur = $id;
        $guard = 0;
        while ($cur !== null && isset($this->existing[$cur]) && $guard < 1000) {
            array_unshift($parts, mb_strtolower($this->existing[$cur]['name']));
            $cur = $this->existing[$cur]['parent_id'];
            $guard++;
        }

        return implode('/', $parts);
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'category';
        }

        $candidate = $base;
        $i = 2;
        while (isset($this->existingSlugs[$candidate]) || isset($this->allocatedSlugs[$candidate])) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        $this->allocatedSlugs[$candidate] = true;

        return $candidate;
    }

    private function loadExisting(): void
    {
        $this->existing = [];
        $this->existingByParentName = [];
        $this->existingByName = [];
        $this->existingSlugs = [];

        Category::query()
            ->get(['id', 'parent_id', 'name', 'slug'])
            ->each(function (Category $c): void {
                $this->existing[$c->id] = [
                    'id' => $c->id,
                    'parent_id' => $c->parent_id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ];
                $this->existingSlugs[$c->slug] = true;
                $lower = mb_strtolower($c->name);
                $this->existingByParentName[($c->parent_id ?? 'null').'|'.$lower] = $c->id;
                $this->existingByName[$lower][] = $c->id;
            });
    }
}

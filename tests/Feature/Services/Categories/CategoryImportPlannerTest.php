<?php

use App\Models\Category;
use App\Services\Categories\CategoryImportNode;
use App\Services\Categories\CategoryImportPlan;
use App\Services\Categories\CategoryImportPlanner;

function plannerNode(CategoryImportPlan $plan, string $path): ?CategoryImportNode
{
    foreach ($plan->nodes as $node) {
        if ($node->path === $path) {
            return $node;
        }
    }

    return null;
}

it('builds a 3-level tree from unordered rows', function () {
    // Child appears before its parent; parent before grandparent.
    $rows = [
        ['name' => 'Belts', 'parent' => 'Sanding'],
        ['name' => 'Sanding', 'parent' => 'Abrasives'],
        ['name' => 'Abrasives', 'parent' => ''],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);

    expect($plan->isValid())->toBeTrue()
        ->and($plan->newCount())->toBe(3)
        ->and($plan->reuseCount())->toBe(0);

    $abrasives = plannerNode($plan, 'abrasives');
    $sanding = plannerNode($plan, 'abrasives/sanding');
    $belts = plannerNode($plan, 'abrasives/sanding/belts');

    expect($abrasives->parentPath)->toBeNull()
        ->and($sanding->parentPath)->toBe('abrasives')
        ->and($belts->parentPath)->toBe('abrasives/sanding');

    // Roots are ordered before their descendants for safe creation.
    $paths = array_map(fn (CategoryImportNode $n) => $n->path, $plan->nodes);
    expect(array_search('abrasives', $paths, true))->toBeLessThan(array_search('abrasives/sanding', $paths, true));
});

it('reuses existing categories and only creates the new leaf', function () {
    $abrasives = Category::factory()->root()->create(['name' => 'Abrasives', 'slug' => 'abrasives']);
    $sanding = Category::factory()->create(['name' => 'Sanding', 'slug' => 'sanding', 'parent_id' => $abrasives->id]);

    $rows = [
        ['name' => 'Abrasives', 'parent' => ''],
        ['name' => 'Sanding', 'parent' => 'Abrasives'],
        ['name' => 'Belts', 'parent' => 'Sanding'],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);

    expect($plan->isValid())->toBeTrue()
        ->and($plan->reuseCount())->toBe(2)
        ->and($plan->newCount())->toBe(1);

    expect(plannerNode($plan, 'abrasives')->existingId)->toBe($abrasives->id)
        ->and(plannerNode($plan, 'abrasives/sanding')->existingId)->toBe($sanding->id)
        ->and(plannerNode($plan, 'abrasives/sanding/belts')->isCreate())->toBeTrue();
});

it('generates unique slugs for same-named nodes under different parents', function () {
    Category::factory()->root()->create(['name' => 'Existing Discs', 'slug' => 'discs']);

    $rows = [
        ['name' => 'Sanding', 'parent' => ''],
        ['name' => 'Grinding', 'parent' => ''],
        ['name' => 'Discs', 'parent' => 'Sanding'],
        ['name' => 'Discs', 'parent' => 'Grinding'],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);

    expect($plan->isValid())->toBeTrue();

    $slugs = [
        plannerNode($plan, 'sanding/discs')->slug,
        plannerNode($plan, 'grinding/discs')->slug,
    ];

    // Both differ from the existing "discs" and from each other.
    expect($slugs[0])->not->toBe('discs')
        ->and($slugs[1])->not->toBe('discs')
        ->and($slugs[0])->not->toBe($slugs[1]);
});

it('flags a blank name with the correct row number', function () {
    $rows = [
        ['name' => 'Abrasives', 'parent' => ''],
        ['name' => '', 'parent' => 'Abrasives'],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);

    expect($plan->isValid())->toBeFalse()
        ->and($plan->errors[0]['row'])->toBe(3) // firstRowNumber 2 + second data row
        ->and($plan->errors[0]['message'])->toContain('name');
});

it('flags an unknown parent reference', function () {
    $rows = [
        ['name' => 'Belts', 'parent' => 'Nonexistent'],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);

    expect($plan->isValid())->toBeFalse()
        ->and($plan->errors[0]['message'])->toContain('Unknown parent');
});

it('flags an ambiguous parent that matches two rows', function () {
    $rows = [
        ['name' => 'Sanding', 'parent' => ''],
        ['name' => 'Sanding', 'parent' => ''],
        ['name' => 'Belts', 'parent' => 'Sanding'],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);

    expect($plan->isValid())->toBeFalse();
    $messages = array_map(fn ($e) => $e['message'], $plan->errors);
    expect(collect($messages)->contains(fn ($m) => str_contains($m, 'Ambiguous parent')))->toBeTrue();
});

it('detects a cycle without infinite looping', function () {
    $rows = [
        ['name' => 'Alpha', 'parent' => 'Beta'],
        ['name' => 'Beta', 'parent' => 'Alpha'],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);

    expect($plan->isValid())->toBeFalse();
    $messages = array_map(fn ($e) => $e['message'], $plan->errors);
    expect(collect($messages)->contains(fn ($m) => str_contains($m, 'Cycle')))->toBeTrue();
});

it('performs no database writes while planning', function () {
    Category::factory()->root()->create(['name' => 'Abrasives', 'slug' => 'abrasives']);
    $before = Category::count();

    (new CategoryImportPlanner)->plan([
        ['name' => 'Abrasives', 'parent' => ''],
        ['name' => 'Sanding', 'parent' => 'Abrasives'],
    ]);

    expect(Category::count())->toBe($before);
});

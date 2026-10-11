<?php

use App\Models\Category;
use App\Services\Categories\CategoryImportCommitter;
use App\Services\Categories\CategoryImportNode;
use App\Services\Categories\CategoryImportPlan;
use App\Services\Categories\CategoryImportPlanner;
use Illuminate\Database\QueryException;

it('commits a 3-level plan with correct parent wiring', function () {
    $rows = [
        ['name' => 'Abrasives', 'parent' => ''],
        ['name' => 'Sanding', 'parent' => 'Abrasives'],
        ['name' => 'Belts', 'parent' => 'Sanding'],
        ['name' => 'Discs', 'parent' => 'Sanding'],
    ];

    $plan = (new CategoryImportPlanner)->plan($rows);
    $result = (new CategoryImportCommitter)->commit($plan);

    expect($result->createdCount)->toBe(4)
        ->and($result->reusedCount)->toBe(0);

    $abrasives = Category::where('name', 'Abrasives')->firstOrFail();
    $sanding = Category::where('name', 'Sanding')->firstOrFail();
    $belts = Category::where('name', 'Belts')->firstOrFail();
    $discs = Category::where('name', 'Discs')->firstOrFail();

    expect($abrasives->parent_id)->toBeNull()
        ->and($sanding->parent_id)->toBe($abrasives->id)
        ->and($belts->parent_id)->toBe($sanding->id)
        ->and($discs->parent_id)->toBe($sanding->id);
});

it('reuses existing parents and only creates the new leaf', function () {
    $abrasives = Category::factory()->root()->create(['name' => 'Abrasives', 'slug' => 'abrasives']);
    $sanding = Category::factory()->create(['name' => 'Sanding', 'slug' => 'sanding', 'parent_id' => $abrasives->id]);

    $plan = (new CategoryImportPlanner)->plan([
        ['name' => 'Abrasives', 'parent' => ''],
        ['name' => 'Sanding', 'parent' => 'Abrasives'],
        ['name' => 'Belts', 'parent' => 'Sanding'],
    ]);

    $result = (new CategoryImportCommitter)->commit($plan);

    expect($result->createdCount)->toBe(1)
        ->and($result->reusedCount)->toBe(2)
        ->and(Category::count())->toBe(3);

    $belts = Category::where('name', 'Belts')->firstOrFail();
    expect($belts->parent_id)->toBe($sanding->id);
});

it('is idempotent when the same CSV is re-planned and re-committed', function () {
    $rows = [
        ['name' => 'Abrasives', 'parent' => ''],
        ['name' => 'Sanding', 'parent' => 'Abrasives'],
        ['name' => 'Belts', 'parent' => 'Sanding'],
    ];

    (new CategoryImportCommitter)->commit((new CategoryImportPlanner)->plan($rows));
    expect(Category::count())->toBe(3);

    // Re-planning now sees everything as existing -> all REUSE.
    $secondPlan = (new CategoryImportPlanner)->plan($rows);
    expect($secondPlan->newCount())->toBe(0)
        ->and($secondPlan->reuseCount())->toBe(3);

    $secondResult = (new CategoryImportCommitter)->commit($secondPlan);
    expect($secondResult->createdCount)->toBe(0)
        ->and(Category::count())->toBe(3);
});

it('rolls the whole import back when a create fails mid-commit', function () {
    // Hand-built (still "valid": no row errors) plan whose two CREATE nodes
    // share a slug — the second insert violates the unique slug index.
    $plan = new CategoryImportPlan(
        nodes: [
            new CategoryImportNode('alpha', 'Alpha', null, null, CategoryImportNode::CREATE, 'dupe', null, null, null, 2),
            new CategoryImportNode('beta', 'Beta', null, null, CategoryImportNode::CREATE, 'dupe', null, null, null, 3),
        ],
        errors: [],
    );

    expect(fn () => (new CategoryImportCommitter)->commit($plan))
        ->toThrow(QueryException::class);

    // First node's insert was rolled back with the failing second one.
    expect(Category::count())->toBe(0);
});

it('refuses to commit a plan that contains errors', function () {
    $plan = new CategoryImportPlan(
        nodes: [],
        errors: [['row' => 2, 'message' => 'Missing required "name".', 'values' => []]],
    );

    expect(fn () => (new CategoryImportCommitter)->commit($plan))
        ->toThrow(RuntimeException::class);

    expect(Category::count())->toBe(0);
});

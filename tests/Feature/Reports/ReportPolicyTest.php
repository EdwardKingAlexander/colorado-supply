<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Str;

function company(string $name): Company
{
    return Company::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(4))]);
}

/**
 * @return array{owner: User, colleague: User, outsider: User, loner: User}
 */
function policyPeople(): array
{
    $acme = company('Acme');

    return [
        'owner' => User::factory()->create(['company_id' => $acme->id]),
        'colleague' => User::factory()->create(['company_id' => $acme->id]),
        'outsider' => User::factory()->create(['company_id' => company('Other')->id]),
        'loner' => User::factory()->create(['company_id' => null]),
    ];
}

test('who may do what with a report', function (string $sharing, array $expected) {
    $people = policyPeople();
    $report = Report::factory()->for($people['owner'], 'owner')->create(match ($sharing) {
        'private' => [],
        'shared' => ['visibility' => 'company'],
        'shared editable' => ['visibility' => 'company', 'company_can_edit' => true],
    });

    foreach ($expected as $who => $abilities) {
        foreach (['view', 'update', 'share', 'delete', 'duplicate'] as $ability) {
            expect($people[$who]->can($ability, $report))
                ->toBe(in_array($ability, $abilities, true), "{$who} {$ability} on a {$sharing} report");
        }
    }
})->with([
    'private' => ['private', [
        'owner' => ['view', 'update', 'share', 'delete', 'duplicate'],
        'colleague' => [],
        'outsider' => [],
        'loner' => [],
    ]],
    'shared' => ['shared', [
        'owner' => ['view', 'update', 'share', 'delete', 'duplicate'],
        'colleague' => ['view', 'duplicate'],
        'outsider' => [],
        'loner' => [],
    ]],
    'shared editable' => ['shared editable', [
        'owner' => ['view', 'update', 'share', 'delete', 'duplicate'],
        'colleague' => ['view', 'update', 'duplicate'],
        'outsider' => [],
        'loner' => [],
    ]],
]);

test('a company-less owner keeps their own reports private to themselves', function () {
    $loner = User::factory()->create(['company_id' => null]);
    $stranger = User::factory()->create(['company_id' => null]);
    $report = Report::factory()->for($loner, 'owner')->create(['company_id' => null, 'visibility' => 'company']);

    expect($loner->can('view', $report))->toBeTrue()
        ->and($stranger->can('view', $report))->toBeFalse();
});

test('a shared report stays with the company when its owner leaves', function () {
    $people = policyPeople();
    $report = Report::factory()->for($people['owner'], 'owner')->sharedWithCompany()->create();

    $people['owner']->update(['company_id' => $people['outsider']->company_id]);

    expect($people['colleague']->can('view', $report))->toBeTrue()
        ->and($people['colleague']->can('update', $report))->toBeFalse()
        ->and($people['owner']->can('update', $report))->toBeTrue()
        ->and($people['outsider']->can('view', $report))->toBeFalse();
});

test('admins may look at but never change customer reports', function () {
    $report = Report::factory()->sharedWithCompany(editable: true)->create();
    $admin = Admin::factory()->create();

    expect($admin->can('viewAny', Report::class))->toBeTrue()
        ->and($admin->can('view', $report))->toBeTrue()
        ->and($admin->can('create', Report::class))->toBeFalse()
        ->and($admin->can('update', $report))->toBeFalse()
        ->and($admin->can('share', $report))->toBeFalse()
        ->and($admin->can('delete', $report))->toBeFalse()
        ->and($admin->can('duplicate', $report))->toBeFalse();
});

test('the visibleTo scope matches the view ability', function () {
    $people = policyPeople();
    $private = Report::factory()->for($people['owner'], 'owner')->create();
    $shared = Report::factory()->for($people['owner'], 'owner')->sharedWithCompany()->create();
    $theirs = Report::factory()->for($people['outsider'], 'owner')->sharedWithCompany()->create();
    $lonerOwn = Report::factory()->for($people['loner'], 'owner')->create(['company_id' => null]);

    foreach ($people as $who => $user) {
        $visible = Report::query()->visibleTo($user)->pluck('id')->sort()->values()->all();
        $expected = collect([$private, $shared, $theirs, $lonerOwn])
            ->filter(fn (Report $report) => $user->can('view', $report))
            ->pluck('id')->sort()->values()->all();

        expect($visible)->toBe($expected, $who);
    }
});

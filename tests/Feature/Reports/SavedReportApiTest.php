<?php

use App\Models\Admin;
use App\Models\Report;
use App\Models\User;
use App\Reporting\ReportService;
use Tests\Feature\Reporting\ReportingFixtures as F;

function ordersDefinition(array $overrides = []): array
{
    return array_merge([
        'dataset' => 'orders',
        'columns' => [['field' => 'order.order_number'], ['field' => 'order.grand_total', 'format' => 'currency']],
    ], $overrides);
}

/**
 * @return array{owner: User, colleague: User, outsider: User}
 */
function apiPeople(): array
{
    $acme = F::tenant('Acme');
    $other = F::tenant('Other');

    return [
        'owner' => $acme['user'],
        'colleague' => User::factory()->create(['company_id' => $acme['company']->id]),
        'outsider' => $other['user'],
    ];
}

test('guests and admins cannot use the report API', function () {
    $this->getJson(route('dashboard.reports.saved.index'))->assertUnauthorized();
    $this->postJson(route('dashboard.reports.preview'), ['definition' => ordersDefinition()])->assertUnauthorized();

    $this->actingAs(Admin::factory()->create(), 'admin')
        ->getJson(route('dashboard.reports.saved.index'))
        ->assertUnauthorized();
});

test('saving stores the normalized definition and returns the report', function () {
    ['owner' => $owner] = apiPeople();

    $response = $this->actingAs($owner)->postJson(route('dashboard.reports.saved.store'), [
        'name' => 'Big orders',
        'description' => 'Everything over $100',
        'definition' => ordersDefinition(['filters' => ['rules' => [['field' => 'order.grand_total', 'op' => 'gt', 'value' => 100]]]]),
    ])->assertCreated();

    $report = Report::query()->sole();

    expect($report->user_id)->toBe($owner->id)
        ->and($report->company_id)->toBe($owner->company_id)
        ->and($report->dataset)->toBe('orders')
        ->and($report->schema_version)->toBe(1)
        ->and($report->visibility)->toBe('private')
        ->and($report->definition['columns'][0])->toBe(['key' => 'order_order_number', 'field' => 'order.order_number'])
        ->and($report->definition['mode'])->toBe('table');

    $response->assertJsonPath('data.name', 'Big orders')
        ->assertJsonPath('data.owner.is_me', true)
        ->assertJsonPath('data.permissions.share', true)
        ->assertJsonPath('data.definition.columns.1.key', 'order_grand_total');
});

test('invalid definitions are rejected with errors under definition.*', function () {
    ['owner' => $owner] = apiPeople();

    $this->actingAs($owner)->postJson(route('dashboard.reports.saved.store'), [
        'name' => 'Broken',
        'definition' => ordersDefinition(['columns' => [['field' => 'order.internal_notes']], 'limit' => 0]),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['definition.columns.0.field', 'definition.limit']);

    $this->actingAs($owner)->postJson(route('dashboard.reports.saved.store'), ['definition' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'definition']);

    expect(Report::query()->count())->toBe(0);
});

test('the library lists my reports and those shared with my company', function () {
    ['owner' => $owner, 'colleague' => $colleague, 'outsider' => $outsider] = apiPeople();
    $mine = Report::factory()->for($owner, 'owner')->create(['name' => 'Mine']);
    $shared = Report::factory()->for($colleague, 'owner')->sharedWithCompany()->create(['name' => 'Team']);
    Report::factory()->for($colleague, 'owner')->create(['name' => 'Colleague private']);
    Report::factory()->for($outsider, 'owner')->sharedWithCompany()->create(['name' => 'Other company']);

    $this->actingAs($owner)->getJson(route('dashboard.reports.saved.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data.mine')
        ->assertJsonPath('data.mine.0.id', $mine->id)
        ->assertJsonCount(1, 'data.shared')
        ->assertJsonPath('data.shared.0.id', $shared->id)
        ->assertJsonPath('data.shared.0.owner.name', $colleague->name)
        ->assertJsonPath('data.shared.0.permissions.update', false);
});

test('reports a user cannot see answer 404 everywhere', function (string $route, string $method) {
    ['owner' => $owner, 'outsider' => $outsider] = apiPeople();
    $report = Report::factory()->for($owner, 'owner')->sharedWithCompany(editable: true)->create();

    $this->actingAs($outsider)
        ->json($method, route($route, $report), ['name' => 'Mine now', 'definition' => ordersDefinition()])
        ->assertNotFound();

    expect($report->fresh()->name)->not->toBe('Mine now');
})->with([
    ['dashboard.reports.saved.show', 'GET'],
    ['dashboard.reports.saved.update', 'PATCH'],
    ['dashboard.reports.saved.destroy', 'DELETE'],
    ['dashboard.reports.saved.duplicate', 'POST'],
    ['dashboard.reports.saved.run', 'POST'],
]);

test('colleagues can open a shared report but only edit it when allowed', function () {
    ['owner' => $owner, 'colleague' => $colleague] = apiPeople();
    $report = Report::factory()->for($owner, 'owner')->sharedWithCompany()->create();

    $this->actingAs($colleague)->getJson(route('dashboard.reports.saved.show', $report))
        ->assertOk()
        ->assertJsonPath('data.permissions.update', false);
    $this->actingAs($colleague)->patchJson(route('dashboard.reports.saved.update', $report), ['name' => 'Renamed'])
        ->assertForbidden();

    $report->update(['company_can_edit' => true]);

    $this->actingAs($colleague)->patchJson(route('dashboard.reports.saved.update', $report), [
        'name' => 'Renamed',
        'definition' => ordersDefinition(['columns' => [['field' => 'order.po_number']]]),
    ])->assertOk()->assertJsonPath('data.name', 'Renamed');

    expect($report->fresh()->definition['columns'][0]['field'])->toBe('order.po_number')
        ->and($report->fresh()->user_id)->toBe($owner->id);
});

test('only the owner can change sharing or delete', function () {
    ['owner' => $owner, 'colleague' => $colleague] = apiPeople();
    $report = Report::factory()->for($owner, 'owner')->sharedWithCompany(editable: true)->create();

    $this->actingAs($colleague)->patchJson(route('dashboard.reports.saved.update', $report), ['visibility' => 'private'])
        ->assertForbidden();
    $this->actingAs($colleague)->deleteJson(route('dashboard.reports.saved.destroy', $report))
        ->assertForbidden();

    $this->actingAs($owner)->patchJson(route('dashboard.reports.saved.update', $report), ['visibility' => 'private'])
        ->assertOk()
        ->assertJsonPath('data.visibility', 'private')
        ->assertJsonPath('data.company_can_edit', false);

    $this->actingAs($owner)->deleteJson(route('dashboard.reports.saved.destroy', $report))->assertOk();
    expect(Report::query()->count())->toBe(0)
        ->and(Report::withTrashed()->count())->toBe(1);
});

test('edit rights are dropped when a report is not shared', function () {
    ['owner' => $owner] = apiPeople();

    $this->actingAs($owner)->postJson(route('dashboard.reports.saved.store'), [
        'name' => 'Private', 'definition' => ordersDefinition(), 'company_can_edit' => true,
    ])->assertCreated()->assertJsonPath('data.company_can_edit', false);
});

test('people without a company cannot share with one', function () {
    $loner = User::factory()->create(['company_id' => null]);

    $this->actingAs($loner)->postJson(route('dashboard.reports.saved.store'), [
        'name' => 'Shared', 'definition' => ordersDefinition(), 'visibility' => 'company',
    ])->assertUnprocessable()->assertJsonValidationErrors('visibility');
});

test('sharing uses the owner\'s current company', function () {
    $loner = User::factory()->create(['company_id' => null]);
    $report = Report::factory()->for($loner, 'owner')->create(['company_id' => null]);
    $company = F::tenant('Joined')['company'];
    $loner->update(['company_id' => $company->id]);

    $this->actingAs($loner)->patchJson(route('dashboard.reports.saved.update', $report), ['visibility' => 'company'])
        ->assertOk();

    expect($report->fresh()->company_id)->toBe($company->id);
});

test('duplicating makes a private copy owned by the person copying', function () {
    ['owner' => $owner, 'colleague' => $colleague] = apiPeople();
    $report = Report::factory()->for($owner, 'owner')->sharedWithCompany()->create(['name' => 'Team report']);

    $this->actingAs($colleague)->postJson(route('dashboard.reports.saved.duplicate', $report))
        ->assertCreated()
        ->assertJsonPath('data.name', 'Copy of Team report')
        ->assertJsonPath('data.visibility', 'private')
        ->assertJsonPath('data.owner.is_me', true);

    expect(Report::query()->where('user_id', $colleague->id)->sole()->definition)->toBe($report->definition);
});

test('running a shared report shows each viewer their own data', function () {
    $acme = F::tenant('Acme');
    $colleague = User::factory()->create(['company_id' => $acme['company']->id]);
    F::order($acme['user'], 10, ['po_number' => 'ACME-COMPANY']);
    F::order($colleague, 20, ['po_number' => 'COLLEAGUE-OWN', 'company_id' => null]);

    $report = Report::factory()->for($acme['user'], 'owner')->sharedWithCompany()->create([
        'definition' => ordersDefinition(['columns' => [['field' => 'order.po_number', 'key' => 'po']], 'sort' => [['column' => 'po']]]),
    ]);

    $this->actingAs($acme['user'])->postJson(route('dashboard.reports.saved.run', $report))
        ->assertOk()
        ->assertJsonPath('data.rows', [['po' => 'ACME-COMPANY']]);

    $this->actingAs($colleague)->postJson(route('dashboard.reports.saved.run', $report))
        ->assertOk()
        ->assertJsonPath('data.rows', [['po' => 'ACME-COMPANY'], ['po' => 'COLLEAGUE-OWN']]);

    expect($report->fresh()->last_run_at)->not->toBeNull();
});

test('a report saved against fields that no longer validate opens with its errors', function () {
    ['owner' => $owner] = apiPeople();
    $report = Report::factory()->for($owner, 'owner')->create([
        'definition' => ordersDefinition(['columns' => [['field' => 'order.retired_field']]]),
    ]);

    $this->actingAs($owner)->getJson(route('dashboard.reports.saved.show', $report))
        ->assertOk()
        ->assertJsonPath('data.definition_errors', ['columns.0.field' => 'Unknown field.']);

    $this->actingAs($owner)->postJson(route('dashboard.reports.saved.run', $report))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('definition.columns.0.field');
});

test('a user can save at most the maximum number of reports', function () {
    ['owner' => $owner] = apiPeople();
    Report::factory()->count(ReportService::MAX_SAVED_REPORTS)->for($owner, 'owner')->create();

    $this->actingAs($owner)->postJson(route('dashboard.reports.saved.store'), ['name' => 'One more', 'definition' => ordersDefinition()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

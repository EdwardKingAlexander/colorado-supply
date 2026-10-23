<?php

use App\Models\Location;
use App\Models\User;
use App\Reporting\Templates\TemplateCatalog;
use Tests\Feature\Reporting\ReportingFixtures as F;

test('preview runs an unsaved definition for the user', function () {
    ['user' => $user] = F::tenant('Acme');
    ['user' => $other] = F::tenant('Other');
    F::order($user, 42, ['po_number' => 'MINE']);
    F::order($other, 99, ['po_number' => 'THEIRS']);

    $this->actingAs($user)->postJson(route('dashboard.reports.preview'), ['definition' => [
        'dataset' => 'orders',
        'columns' => [['field' => 'order.po_number', 'key' => 'po'], ['field' => 'order.grand_total', 'key' => 'total']],
        'totals' => ['grand' => true],
    ]])
        ->assertOk()
        ->assertJsonPath('data.rows', [['po' => 'MINE', 'total' => 42]])
        ->assertJsonPath('data.totals.total', 42)
        ->assertJsonPath('data.row_count', 1)
        ->assertJsonPath('data.truncated', false)
        ->assertJsonPath('data.columns.0.label', 'PO number');
});

test('preview errors are reported by definition path', function () {
    ['user' => $user] = F::tenant('Acme');

    $this->actingAs($user)->postJson(route('dashboard.reports.preview'), ['definition' => [
        'dataset' => 'orders',
        'mode' => 'summary',
        'columns' => [['field' => 'order.grand_total', 'aggregate' => 'sum', 'key' => 'spend']],
        'calculated' => [['key' => 'x', 'formula' => 'spend +']],
    ]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['definition.calculated.0.formula' => 'The formula ends too soon (at character 8).']);

    $this->actingAs($user)->postJson(route('dashboard.reports.preview'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('definition');
});

test('identical previews are cached per user for a minute', function () {
    ['user' => $user, 'company' => $company] = F::tenant('Acme');
    $colleague = User::factory()->create(['company_id' => $company->id]);
    $definition = ['dataset' => 'orders', 'columns' => [['field' => 'order.id', 'aggregate' => 'count', 'key' => 'n']], 'mode' => 'summary'];

    F::order($user);
    $this->actingAs($user)->postJson(route('dashboard.reports.preview'), ['definition' => $definition])->assertJsonPath('data.rows.0.n', 1);

    F::order($user);
    $this->actingAs($user)->postJson(route('dashboard.reports.preview'), ['definition' => $definition])->assertJsonPath('data.rows.0.n', 1);
    $this->actingAs($colleague)->postJson(route('dashboard.reports.preview'), ['definition' => $definition])->assertJsonPath('data.rows.0.n', 2);

    $this->travel(61)->seconds();
    $this->actingAs($user)->postJson(route('dashboard.reports.preview'), ['definition' => $definition])->assertJsonPath('data.rows.0.n', 2);
});

test('previews are rate limited', function () {
    ['user' => $user] = F::tenant('Acme');
    $definition = ['dataset' => 'orders', 'columns' => [['field' => 'order.id']]];

    for ($i = 0; $i < 60; $i++) {
        $this->actingAs($user)->postJson(route('dashboard.reports.preview'), ['definition' => $definition])->assertOk();
    }

    $this->actingAs($user)->postJson(route('dashboard.reports.preview'), ['definition' => $definition])->assertTooManyRequests();
});

test('the catalog describes every dataset and only the user\'s own locations', function () {
    ['user' => $user, 'location' => $hq, 'sublocation' => $annex] = F::tenant('Acme');
    F::tenant('Other');
    $archived = Location::create(['company_id' => $user->company_id, 'name' => 'Old yard', 'slug' => 'old-yard']);
    $archived->delete();

    $response = $this->actingAs($user)->getJson(route('dashboard.reports.catalog'))->assertOk();

    $datasets = collect($response->json('data.datasets'));
    $status = collect($datasets->firstWhere('key', 'orders')['fields'])->firstWhere('key', 'order.status');

    expect($datasets->pluck('key')->all())->toBe(['orders', 'order_lines', 'payments', 'shipments', 'quotes', 'quote_lines'])
        ->and($status['type'])->toBe('enum')
        ->and($status['operators'])->toContain('in')
        ->and($status['aggregates'])->toBe(['count', 'count_distinct'])
        ->and($status['options'][0])->toBe(['value' => 'draft', 'label' => 'Draft'])
        ->and($response->json('data.formula_functions'))->toContain('ROUND', 'SUM')
        ->and($response->json('data.locations'))->toBe([
            ['id' => $hq->id, 'name' => 'Acme HQ', 'archived' => false, 'children' => [['id' => $annex->id, 'name' => 'Acme Annex', 'archived' => false]]],
            ['id' => $archived->id, 'name' => 'Old yard', 'archived' => true, 'children' => []],
        ]);
});

test('every template is valid and runs', function (array $template) {
    ['user' => $user, 'location' => $hq] = F::tenant('Acme');
    $order = F::order($user, 30, ['created_at' => now()->subDays(3)]);
    F::line($order, $hq, 15, 2);
    F::payment($order, ['created_at' => now()->subDays(3)]);
    F::shipment($order, ['shipped_at' => now()->subDays(3), 'delivered_at' => now()->subDay()]);
    F::quote($user, 30, ['created_at' => now()->subDays(5)]);

    $result = F::run($user, $template['definition']);

    expect($result->columns)->not->toBeEmpty()
        ->and($template['name'])->not->toBe('')
        ->and($template['description'])->not->toBe('');
})->with(fn () => array_map(fn (array $template) => [$template], TemplateCatalog::all()));

test('templates are listed by the API', function () {
    ['user' => $user] = F::tenant('Acme');

    $this->actingAs($user)->getJson(route('dashboard.reports.templates'))
        ->assertOk()
        ->assertJsonCount(count(TemplateCatalog::all()), 'data')
        ->assertJsonPath('data.0.key', 'purchasing_month');

    expect(TemplateCatalog::find('top_products')['definition']['limit'])->toBe(25)
        ->and(TemplateCatalog::find('missing'))->toBeNull();
});

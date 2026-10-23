<?php

use App\Reporting\DatasetRegistry;
use App\Reporting\Datasets\Dataset;
use App\Reporting\Fields\Join;
use App\Reporting\Sql\MySqlDialect;
use App\Reporting\Sql\SqliteDialect;

test('the registry lists the six datasets', function () {
    expect(array_keys((new DatasetRegistry)->all()))
        ->toBe(['orders', 'order_lines', 'payments', 'shipments', 'quotes', 'quote_lines']);
});

test('unknown datasets are not found', function () {
    $registry = new DatasetRegistry;

    expect($registry->find('users'))->toBeNull();
    $registry->get('users');
})->throws(InvalidArgumentException::class);

test('every dataset has fields with unique keys and labels', function (Dataset $dataset) {
    $fields = $dataset->fields();

    expect($fields)->not->toBeEmpty()
        ->and($dataset->field('nope'))->toBeNull();

    foreach ($fields as $key => $field) {
        expect($field->key)->toBe($key)
            ->and($field->label)->not->toBe('')
            ->and($key)->toMatch('/^[a-z_]+\.[a-z_]+$/');
    }
})->with(fn () => (new DatasetRegistry)->all());

test('every join a field names exists, with its dependencies', function (Dataset $dataset) {
    $joins = (fn () => $this->joins())->call($dataset);
    $required = (fn () => $this->requiredJoins())->call($dataset);

    expect($joins)->toBeArray();

    foreach ([...$required, ...array_merge(...array_map(fn ($field) => $field->joins, array_values($dataset->fields())))] as $key) {
        expect($joins)->toHaveKey($key);
    }

    foreach ($joins as $join) {
        /** @var Join $join */
        foreach ($join->dependsOn as $dependency) {
            expect($joins)->toHaveKey($dependency);
        }
    }
})->with(fn () => (new DatasetRegistry)->all());

test('no field exposes a hidden column', function (Dataset $dataset) {
    $forbidden = [
        '/\bcost\b/', '/internal_notes/', '/\bmeta\b/', '/gateway_/', '/failure_/', '/created_by/', '/updated_by/',
        '/sales_rep_id/', '/walk_in_(org|contact|email|phone|billing|shipping)/', '/pickup_code/', '/vendor_id/',
        '/cash_card/', '/\bnotes\b/', '/contact_(email|phone)/', '/billing_address/', '/shipping_address/',
        '/\bprice\b/', '/list_price/', '/gsa_price/', '/contract_price/',
    ];

    foreach ($dataset->fields() as $field) {
        foreach ([new SqliteDialect, new MySqlDialect] as $dialect) {
            $sql = $field->sql($dialect).' '.($field->parentExpression ?? '');

            foreach ($forbidden as $pattern) {
                expect(preg_match($pattern, $sql))->toBe(0, "{$dataset->key()} / {$field->key} exposes {$pattern}: {$sql}");
            }
        }
    }
})->with(fn () => (new DatasetRegistry)->all());

test('enum fields list their options and location fields name a parent column', function (Dataset $dataset) {
    foreach ($dataset->fields() as $field) {
        match ($field->type->value) {
            'enum' => expect($field->options)->not->toBeEmpty(),
            'location' => expect($field->parentExpression)->not->toBeNull()->and($field->selectable)->toBeFalse(),
            default => expect($field->options)->toBe([]),
        };
    }
})->with(fn () => (new DatasetRegistry)->all());

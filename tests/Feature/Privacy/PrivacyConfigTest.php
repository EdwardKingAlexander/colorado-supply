<?php

test('every registered cookie belongs to a defined consent category', function () {
    $categories = array_keys(config('privacy.categories'));

    foreach (config('privacy.cookies') as $cookie) {
        expect($cookie['category'])->toBeIn($categories);
        expect($cookie)->toHaveKeys(['name', 'provider', 'category', 'purpose', 'lifetime']);
    }
});

test('the essential category is locked and present', function () {
    expect(config('privacy.categories.essential.locked'))->toBeTrue();
});

test('dsr and retention windows are configured', function () {
    expect(config('privacy.dsr.response_days'))->toBe(45)
        ->and(config('privacy.dsr.extension_days'))->toBe(45)
        ->and(config('privacy.retention.unverified_dsr_days'))->toBeInt()
        ->and(config('privacy.policy_version'))->toBeString();
});

test('legal hold entries reference real model classes', function () {
    foreach (config('privacy.legal_hold') as $modelClass) {
        expect(class_exists($modelClass))->toBeTrue();
    }
});

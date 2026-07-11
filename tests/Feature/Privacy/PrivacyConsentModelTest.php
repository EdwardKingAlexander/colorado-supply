<?php

use App\Models\PrivacyConsent;
use App\Models\User;
use Illuminate\Support\Str;

test('factory creates a valid consent receipt', function () {
    $consent = PrivacyConsent::factory()->create();

    expect($consent->visitor_uuid)->toBeString()
        ->and($consent->categories)->toBeArray()->toContain('essential')
        ->and($consent->gpc_applied)->toBeFalse()
        ->and($consent->policy_version)->toBe(config('privacy.policy_version'));
});

test('latestForVisitor returns the most recent receipt for that visitor only', function () {
    $uuid = (string) Str::uuid();

    PrivacyConsent::factory()->create(); // unrelated visitor
    $older = PrivacyConsent::factory()->essentialOnly()->create(['visitor_uuid' => $uuid]);
    $newest = PrivacyConsent::factory()->create(['visitor_uuid' => $uuid]);

    $found = PrivacyConsent::latestForVisitor($uuid);

    expect($found->id)->toBe($newest->id)
        ->and($found->id)->not->toBe($older->id);
});

test('allowsCategory reflects stored categories', function () {
    $consent = PrivacyConsent::factory()->essentialOnly()->create();

    expect($consent->allowsCategory('essential'))->toBeTrue()
        ->and($consent->allowsCategory('analytics'))->toBeFalse();
});

test('gpcApplied factory state records an honored signal', function () {
    $consent = PrivacyConsent::factory()->gpcApplied()->create();

    expect($consent->gpc_applied)->toBeTrue()
        ->and($consent->allowsCategory('analytics'))->toBeFalse();
});

test('receipts survive user deletion with user_id nulled', function () {
    $user = User::factory()->create();
    $consent = PrivacyConsent::factory()->create(['user_id' => $user->id]);

    $user->delete();

    expect($consent->fresh()->user_id)->toBeNull();
});

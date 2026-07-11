<?php

use App\Models\PrivacyConsent;
use App\Models\User;
use Illuminate\Support\Str;

test('a visitor can save consent and receives a readable first-party cookie', function () {
    $response = $this->postJson(route('privacy.consent.store'), [
        'categories' => ['essential', 'analytics'],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('consent.categories', ['essential', 'analytics'])
        ->assertJsonPath('gpc_applied', false)
        ->assertPlainCookie(config('privacy.consent_cookie.name'));

    $cookie = $response->getCookie(config('privacy.consent_cookie.name'), false);
    $payload = json_decode($cookie->getValue(), true);

    expect($cookie->isHttpOnly())->toBeFalse()
        ->and($payload['uuid'])->toBeUuid()
        ->and($payload['version'])->toBe(config('privacy.policy_version'))
        ->and($payload['categories'])->toBe(['essential', 'analytics']);

    $this->assertDatabaseHas('privacy_consents', [
        'visitor_uuid' => $payload['uuid'],
        'gpc_applied' => false,
        'policy_version' => config('privacy.policy_version'),
    ]);
});

test('essential consent is always present even when omitted by an untrusted client', function () {
    $this->postJson(route('privacy.consent.store'), [
        'categories' => ['analytics'],
    ])
        ->assertOk()
        ->assertJsonPath('consent.categories', ['analytics', 'essential']);
});

test('unknown consent categories are rejected', function () {
    $this->postJson(route('privacy.consent.store'), [
        'categories' => ['essential', 'unknown'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('categories.1');
});

test('gpc overrides an attempted analytics grant and records an audit receipt', function () {
    $response = $this->withHeaders(['Sec-GPC' => '1'])
        ->postJson(route('privacy.consent.store'), [
            'categories' => ['essential', 'analytics', 'marketing'],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('consent.categories', ['essential'])
        ->assertJsonPath('gpc_applied', true);

    $receipt = PrivacyConsent::query()->latest('id')->firstOrFail();

    expect($receipt->gpc_applied)->toBeTrue()
        ->and($receipt->categories)->toBe(['essential']);
});

test('client-side gpc detection can explicitly record the honored signal', function () {
    $this->postJson(route('privacy.consent.store'), [
        'categories' => ['essential', 'analytics'],
        'gpc' => true,
    ])
        ->assertOk()
        ->assertJsonPath('consent.categories', ['essential'])
        ->assertJsonPath('gpc_applied', true);
});

test('an existing visitor uuid is reused for subsequent receipts', function () {
    $uuid = (string) Str::uuid();
    $cookie = json_encode([
        'uuid' => $uuid,
        'categories' => ['essential'],
        'version' => config('privacy.policy_version'),
    ]);

    $this->withCredentials()
        ->withUnencryptedCookie(config('privacy.consent_cookie.name'), $cookie)
        ->postJson(route('privacy.consent.store'), [
            'categories' => ['essential', 'analytics'],
        ])
        ->assertOk()
        ->assertJsonPath('consent.uuid', $uuid);

    expect(PrivacyConsent::query()->where('visitor_uuid', $uuid)->count())->toBe(1);
});

test('authenticated consent receipts attach the current user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('privacy.consent.store'), [
            'categories' => ['essential'],
        ])
        ->assertOk();

    $receipt = PrivacyConsent::query()->latest('id')->firstOrFail();

    expect($receipt->user_id)->toBe($user->id)
        ->and($receipt->ip_hash)->toHaveLength(64)
        ->and($receipt->ip_hash)->not->toBe('127.0.0.1');
});

test('the consent endpoint is rate limited', function () {
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->postJson(route('privacy.consent.store'), [
            'categories' => ['essential'],
        ])->assertOk();
    }

    $this->postJson(route('privacy.consent.store'), [
        'categories' => ['essential'],
    ])->assertTooManyRequests();
});

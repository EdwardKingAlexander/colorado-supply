<?php

use App\Support\Privacy\ConsentCookie;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('gpc header is exposed to inertia pages', function () {
    $this->withHeaders(['Sec-GPC' => '1'])
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('privacy.gpc', true)
            ->where('privacy.policyVersion', config('privacy.policy_version'))
        );
});

test('requests without gpc header share gpc as false', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('privacy.gpc', false)
            ->where('privacy.consent', null)
        );
});

test('a valid consent cookie is shared with pages', function () {
    $uuid = (string) Str::uuid();

    $cookie = json_encode([
        'uuid' => $uuid,
        'categories' => ['essential', 'analytics'],
        'version' => config('privacy.policy_version'),
    ]);

    $this->withUnencryptedCookie('cs_privacy_consent', $cookie)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('privacy.consent.uuid', $uuid)
            ->where('privacy.consent.categories', ['essential', 'analytics'])
            ->where('privacy.consent.version', config('privacy.policy_version'))
        );
});

test('malformed consent cookies are shared as null', function (string $value) {
    $this->withUnencryptedCookie('cs_privacy_consent', $value)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('privacy.consent', null));
})->with([
    'not json' => 'garbage',
    'missing uuid' => json_encode(['categories' => ['analytics'], 'version' => '2026-07-10']),
    'invalid uuid' => json_encode(['uuid' => 'nope', 'categories' => ['analytics'], 'version' => '2026-07-10']),
    'missing version' => json_encode(['uuid' => '5cf1c8f4-9c1e-4f7a-8b9a-2f4dfe58f000', 'categories' => ['analytics']]),
]);

test('consent cookie parsing filters unknown categories and forces essential', function () {
    $parsed = ConsentCookie::parse(json_encode([
        'uuid' => '5cf1c8f4-9c1e-4f7a-8b9a-2f4dfe58f000',
        'categories' => ['analytics', 'bogus-category', 42],
        'version' => '2026-07-10',
    ]));

    expect($parsed)->not->toBeNull()
        ->and($parsed['categories'])->toContain('essential')
        ->and($parsed['categories'])->toContain('analytics')
        ->and($parsed['categories'])->not->toContain('bogus-category');
});

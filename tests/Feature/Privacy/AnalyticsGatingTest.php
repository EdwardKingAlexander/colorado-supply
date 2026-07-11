<?php

use Inertia\Testing\AssertableInertia as Assert;

test('analytics consent defaults are rendered before configuration and script loading', function () {
    $html = $this->get('/')->assertOk()->getContent();

    $defaultPosition = strpos($html, "window.gtag('consent', 'default'");
    $configPosition = strpos($html, "window.gtag('config'");
    $scriptPosition = strpos($html, 'googletagmanager.com/gtag/js');

    expect($defaultPosition)->not->toBeFalse()
        ->and($configPosition)->not->toBeFalse()
        ->and($scriptPosition)->not->toBeFalse()
        ->and($defaultPosition)->toBeLessThan($configPosition)
        ->and($defaultPosition)->toBeLessThan($scriptPosition)
        ->and($html)->toContain("analytics_storage: 'denied'")
        ->and($html)->toContain("ad_personalization: 'denied'")
        ->and($html)->toContain('allow_google_signals: false');
});

test('the analytics bootstrap includes gpc as a grant-blocking condition', function () {
    $html = $this->withHeaders(['Sec-GPC' => '1'])
        ->get('/')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('const serverGpc = true')
        ->and($html)->toContain('if (!gpc && consent?.version === policyVersion');
});

test('privacy category metadata is shared from the server registry', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('privacy.categories', config('privacy.categories'))
            ->where('privacy.policyVersion', config('privacy.policy_version'))
        );
});

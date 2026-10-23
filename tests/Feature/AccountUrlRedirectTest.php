<?php

use App\Models\User;

// Account pages moved under /account (ai/modules/customer-portal-ui-rebrand-complete, phase 7).
test('old account addresses redirect permanently to /account', function (string $legacy, string $current) {
    $this->get($legacy)->assertStatus(301)->assertRedirect($current);
})->with([
    ['/dashboard', '/account'],
    ['/dashboard/reports', '/account/reports'],
    ['/dashboard/reports/export', '/account/reports/export'],
    ['/dashboard/locations', '/account/locations'],
    ['/dashboard/organization-history', '/account/organization-history'],
    ['/profile', '/account/profile'],
    ['/notifications', '/account/notifications'],
    ['/company', '/account/company'],
]);

test('redirects keep the query string', function () {
    $this->get('/dashboard/reports?group_by=location&range=last_30_days')
        ->assertStatus(301)
        ->assertRedirect('/account/reports?group_by=location&range=last_30_days');
});

test('account pages are served from their new addresses', function (string $route, string $path) {
    expect(route($route, absolute: false))->toBe($path);
})->with([
    ['dashboard', '/account'],
    ['dashboard.reports', '/account/reports'],
    ['profile.edit', '/account/profile'],
    ['notifications.index', '/account/notifications'],
    ['company.index', '/account/company'],
]);

test('a signed-in customer following an old emailed link lands on the page', function () {
    $this->actingAs(User::factory()->create());

    $this->followingRedirects()
        ->get('/notifications')
        ->assertOk();
});

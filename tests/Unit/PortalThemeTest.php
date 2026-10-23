<?php

use App\Support\PortalTheme;

test('signed-in portal pages follow the theme preference', function (string $component) {
    expect(PortalTheme::appliesTo($component))->toBeTrue();
})->with([
    'Dashboard',
    'Dashboard/Reports',
    'Profile/Edit',
    'Company/Index',
    'Notifications/Index',
    'Sam/Favorites',
    'Store/StoreIndex',
    'Store/ProductDetail',
    'Store/Cart',
    'Store/CheckoutSuccess',
]);

test('public, auth and maintenance pages stay forced dark', function (?string $component) {
    expect(PortalTheme::appliesTo($component))->toBeFalse();
})->with([
    'Welcome',
    'RepairServices/Index',
    'Auth/Login',
    'Store/Unavailable',
    'DashboardPreview',
    '',
    null,
]);

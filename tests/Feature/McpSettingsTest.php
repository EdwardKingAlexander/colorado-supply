<?php

use App\Models\McpSetting;
use App\Support\McpSettings;

it('stores MCP payloads securely and returns decrypted values', function () {
    $payload = [
        'url' => 'https://vendor.example.com/login',
        'username' => 'buyer@example.com',
        'password' => 'super-secret',
    ];

    McpSettings::put('vendor-portal-login', $payload, 'Vendor portal credentials');

    $stored = McpSettings::for('vendor-portal-login');

    expect($stored)
        ->toMatchArray([
            'url' => 'https://vendor.example.com/login',
            'username' => 'buyer@example.com',
            'password' => 'super-secret',
        ]);

    $raw = McpSetting::query()
        ->firstWhere('key', 'vendor-portal-login')
        ->getRawOriginal('value');

    expect($raw)->not->toContain('super-secret');

    $this->assertDatabaseHas('mcp_settings', [
        'key' => 'vendor-portal-login',
        'description' => 'Vendor portal credentials',
    ]);
});

it('returns sane defaults when a setting is missing', function () {
    $default = ['foo' => 'bar'];

    $payload = McpSettings::for('does-not-exist', $default);

    expect($payload)->toBe($default);
});

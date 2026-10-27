<?php

use App\Models\Admin;
use App\Models\User;

it('counts only accounts that still depend on email-code MFA', function () {
    User::factory()->create(['email' => 'legacy@example.com', 'two_factor_method' => 'email', 'two_factor_confirmed_at' => now()]);
    User::factory()->create(['two_factor_method' => 'totp', 'two_factor_confirmed_at' => now()]);
    User::factory()->create(['two_factor_method' => 'email', 'two_factor_confirmed_at' => null]);
    User::factory()->create();

    Admin::factory()->create(['email' => 'emailonly@example.com', 'has_email_authentication' => true]);
    Admin::factory()->withAppAuthentication()->create(['has_email_authentication' => true]);
    Admin::factory()->withAppAuthentication()->create();

    $this->artisan('mfa:email-method-report')
        ->expectsOutput('Customers using email codes: 1')
        ->expectsOutput('Admins whose only factor is email codes: 1')
        ->doesntExpectOutputToContain('legacy@example.com')
        ->assertExitCode(0);

    $this->artisan('mfa:email-method-report', ['--list' => true])
        ->expectsOutputToContain('legacy@example.com')
        ->expectsOutputToContain('emailonly@example.com')
        ->assertExitCode(0);
});

it('reports zero when nobody uses email codes', function () {
    User::factory()->create(['two_factor_method' => 'totp', 'two_factor_confirmed_at' => now()]);

    $this->artisan('mfa:email-method-report')
        ->expectsOutput('Customers using email codes: 0')
        ->expectsOutput('Admins whose only factor is email codes: 0')
        ->assertExitCode(0);
});

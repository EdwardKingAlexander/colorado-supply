<?php

use App\Models\Admin;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('authenticated admins are redirected to the admin dashboard', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get('/dashboard');

    $response->assertRedirect(route('filament.admin.pages.dashboard'));
});

test('admins logging in through the real login flow reach the dashboard without a 403', function () {
    // Regression test: actingAs($admin, 'admin') also switches Laravel's
    // default auth guard for the rest of the test, which previously masked
    // a bug where DashboardFilterRequest::authorize() checked only the
    // default guard via $this->user(). A real browser session keeps the
    // default guard as "web" even when authenticated only on "admin".
    $admin = Admin::factory()->create(['password' => bcrypt('password')]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('filament.admin.pages.dashboard'));
});

<?php

use App\Filament\Pages\SamFavorites;
use App\Filament\Pages\SamInsightsDashboard;
use App\Models\Admin;
use App\Models\SamOpportunity;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('opens the admin favorites destination without a customer login', function () {
    $admin = Admin::factory()->withAppAuthentication()->create();
    $opportunity = SamOpportunity::factory()->create(['url' => 'https://sam.gov/opp/123/view']);
    $this->actingAs($admin, 'admin');
    expect(Auth::guard('web')->check())->toBeFalse();
    $url = SamFavorites::getUrl(['focus' => $opportunity->id], panel: 'admin');
    $this->get($url)->assertOk()->assertSee($opportunity->title)->assertSee('Add to favorites')->assertSee($opportunity->url);
    Livewire::test(SamInsightsDashboard::class)->assertTableActionHasUrl('open', $url, $opportunity);
    expect(DB::table('sam_opportunity_admin_favorites')->count())->toBe(0);
});

it('keeps favorites separate for admins and customers even with the same numeric id', function () {
    $admin = Admin::factory()->create();
    $other = Admin::factory()->create();
    $customer = User::factory()->create(['id' => $admin->id]);
    $mine = SamOpportunity::factory()->create();
    $theirs = SamOpportunity::factory()->create();
    $customer->favoriteSamOpportunities()->attach($mine);
    $other->favoriteSamOpportunities()->attach($theirs);
    $this->actingAs($customer, 'web')->actingAs($admin, 'admin');
    Livewire::test(SamFavorites::class)
        ->assertCanNotSeeTableRecords([$mine, $theirs])
        ->call('favorite', $mine->id)->call('favorite', $mine->id)
        ->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$theirs])
        ->call('unfavorite', $theirs->id)
        ->callTableAction('unfavorite', $mine)
        ->assertCanNotSeeTableRecords([$mine]);
    expect($admin->favoriteSamOpportunities()->count())->toBe(0)
        ->and($customer->favoriteSamOpportunities()->whereKey($mine->id)->exists())->toBeTrue()
        ->and($other->favoriteSamOpportunities()->whereKey($theirs->id)->exists())->toBeTrue();
});

it('shows the selected opportunity without requiring or creating a favorite', function () {
    $this->actingAs(Admin::factory()->create(), 'admin');
    $opportunity = SamOpportunity::factory()->create(['title' => 'Selected opportunity ABC', 'description' => '<script>unsafe()</script>']);
    Livewire::withQueryParams(['focus' => $opportunity->id])->test(SamFavorites::class)
        ->assertSet('focus', $opportunity->id)->assertSee('Selected opportunity ABC')
        ->assertDontSee('<script>unsafe()</script>', escape: false)
        ->call('favorite', $opportunity->id)->assertSee('Remove favorite')
        ->call('unfavorite', $opportunity->id)->assertSee('Add to favorites');
    expect(DB::table('sam_opportunity_admin_favorites')->count())->toBe(0);
});

it('requires admin authentication and keeps invalid focus requests unavailable', function () {
    $url = SamFavorites::getUrl(panel: 'admin');
    $this->get($url)->assertRedirect('/admin/login');
    $this->actingAs(User::factory()->create(), 'web');
    $this->get($url)->assertRedirect('/admin/login');
    Livewire::test(SamFavorites::class)->assertForbidden();
    $this->actingAs(Admin::factory()->withAppAuthentication()->create(), 'admin');
    $this->get($url.'?focus=bad')->assertNotFound();
    $this->get($url.'?focus=999999')->assertNotFound();
});

it('only links the source button to SAM government URLs', function () {
    $opportunity = new SamOpportunity(['url' => 'javascript:alert(1)']);
    expect(SamFavorites::sourceUrl($opportunity))->toBeNull();
    $opportunity->url = 'https://sam.gov.attacker.invalid/';
    expect(SamFavorites::sourceUrl($opportunity))->toBeNull();
    $opportunity->url = 'https://sam.gov/opp/123/view';
    expect(SamFavorites::sourceUrl($opportunity))->toBe($opportunity->url);
});

it('counts customer and admin favorites in Insights', function () {
    $admin = Admin::factory()->create();
    $customer = User::factory()->create();
    $opportunity = SamOpportunity::factory()->create();
    $admin->favoriteSamOpportunities()->attach($opportunity);
    $customer->favoriteSamOpportunities()->attach($opportunity);
    $this->actingAs($admin, 'admin');

    Livewire::test(SamInsightsDashboard::class)
        ->assertTableColumnStateSet('favorites_count', 2, $opportunity);
});

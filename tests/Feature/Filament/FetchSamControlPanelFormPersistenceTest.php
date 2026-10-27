<?php

use App\Filament\Pages\FetchSamControlPanel;
use App\Jobs\FetchSamOpportunitiesJob;
use App\Models\Admin;
use App\Support\SamStatePath;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->initialObLevel = ob_get_level();
    $this->statePath = SamStatePath::opportunities();
    $this->originalState = file_exists($this->statePath) ? file_get_contents($this->statePath) : null;

    $this->actingAs(Admin::factory()->create(), 'admin');

    Queue::fake();
});

afterEach(function () {
    if ($this->originalState !== null) {
        file_put_contents($this->statePath, $this->originalState);
    } elseif (file_exists($this->statePath)) {
        unlink($this->statePath);
    }

    while (ob_get_level() > $this->initialObLevel) {
        ob_end_clean();
    }
});

it('keeps the selected NAICS codes after the page reloads', function () {
    $selected = ['423840', '423450', '339999', '332722'];

    Livewire::test(FetchSamControlPanel::class)
        ->fillForm(['naics_override' => $selected, 'small_business_only' => true, 'days_back' => 14])
        ->call('executeFetch');

    Queue::assertPushed(
        FetchSamOpportunitiesJob::class,
        fn (FetchSamOpportunitiesJob $job) => $job->params['naics_override'] === $selected,
    );

    // A fresh mount is what the browser gets after the post-fetch reload.
    Livewire::test(FetchSamControlPanel::class)
        ->assertSet('formData.naics_override', $selected)
        ->assertSet('formData.small_business_only', true)
        ->assertSet('formData.days_back', 14);
});

it('drops remembered NAICS codes that are no longer offered', function () {
    session([FetchSamControlPanel::FORM_SESSION_KEY => [
        'naics_override' => ['423450', '999999'],
        'unexpected_field' => 'ignored',
    ]]);

    Livewire::test(FetchSamControlPanel::class)
        ->assertSet('formData.naics_override', ['423450'])
        ->assertNotSet('formData.unexpected_field', 'ignored');
});

it('falls back to the primary NAICS defaults when nothing valid is remembered', function () {
    session([FetchSamControlPanel::FORM_SESSION_KEY => ['naics_override' => ['999999']]]);

    Livewire::test(FetchSamControlPanel::class)
        ->assertSet('formData.naics_override', config('sam_opportunities.naics_codes.primary'));
});

it('shows match counts for every searched NAICS code, including zero', function () {
    file_put_contents($this->statePath, json_encode([
        'success' => true,
        'fetched_at' => now()->toIso8601String(),
        'query' => [
            'date_range' => '09/27/2026 to 10/04/2026',
            'naics_codes' => ['339113', '423710'],
        ],
        'summary' => ['total_records' => 2, 'total_after_dedup' => 2, 'returned' => 2, 'limit' => 1000],
        'opportunities' => [
            ['title' => 'A', 'naics_code' => '339113', 'solicitation_number' => 'A-1', 'notice_type' => 'Solicitation', 'posted_date' => null, 'response_deadline' => null, 'agency_name' => 'X'],
            ['title' => 'B', 'naics_code' => '339113', 'solicitation_number' => 'B-1', 'notice_type' => 'Solicitation', 'posted_date' => null, 'response_deadline' => null, 'agency_name' => 'Y'],
        ],
    ]));

    Livewire::test(FetchSamControlPanel::class)
        ->assertSee('339113 · 2')
        ->assertSee('423710 · 0');
});

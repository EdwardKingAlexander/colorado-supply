<?php

use App\Filament\Pages\FetchSamControlPanel;
use App\Jobs\FetchSamOpportunitiesJob;
use App\Models\Admin;
use App\Support\SamParameterResolver;
use App\Support\SamSearchParameters;
use App\Support\SamStatePath;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->initialObLevel = ob_get_level();
    $this->actingAs(Admin::factory()->create(), 'admin');
    Queue::fake();
});

afterEach(function () {
    while (ob_get_level() > $this->initialObLevel) {
        ob_end_clean();
    }
});

function writePscState(array $overrides = []): void
{
    file_put_contents(SamStatePath::opportunities(), json_encode(array_replace_recursive([
        'success' => true,
        'fetched_at' => now()->toIso8601String(),
        'query' => ['date_range' => '09/20/2026 to 10/04/2026', 'naics_codes' => ['332722'], 'psc_codes' => ['53', '59']],
        'summary' => ['total_records' => 3, 'total_after_dedup' => 3, 'returned' => 3, 'limit' => 1000, 'found_only_via_psc' => 2],
        'opportunities' => [
            ['notice_id' => 'a', 'title' => 'A', 'naics_code' => '332722', 'psc_code' => '53', 'matched_terms' => ['naics:332722', 'psc:53'], 'solicitation_number' => 'A-1', 'notice_type' => 'Solicitation', 'posted_date' => null, 'response_deadline' => null, 'agency_name' => 'DLA'],
            ['notice_id' => 'b', 'title' => 'B', 'naics_code' => '332510', 'psc_code' => '53', 'matched_terms' => ['psc:53'], 'solicitation_number' => 'B-1', 'notice_type' => 'Solicitation', 'posted_date' => null, 'response_deadline' => null, 'agency_name' => 'DLA'],
            ['notice_id' => 'c', 'title' => 'C', 'naics_code' => '334417', 'psc_code' => '5999', 'matched_terms' => ['psc:53'], 'solicitation_number' => 'C-1', 'notice_type' => 'Solicitation', 'posted_date' => null, 'response_deadline' => null, 'agency_name' => 'DLA'],
        ],
    ], $overrides)));
}

it('defaults the PSC picker to the configured DLA groups and 4-character PSCs', function () {
    Livewire::test(FetchSamControlPanel::class)
        ->assertSet('formData.psc_search', (new SamSearchParameters)->defaultPscSearch());
});

it('dispatches the PSC selection and remembers it across reloads, including an empty one', function () {
    Livewire::test(FetchSamControlPanel::class)
        ->fillForm(['psc_search' => ['53', '43']])
        ->call('executeFetch');

    Queue::assertPushed(FetchSamOpportunitiesJob::class, fn ($job) => $job->params['psc_search'] === ['53', '43']);

    Livewire::test(FetchSamControlPanel::class)->assertSet('formData.psc_search', ['53', '43']);

    // Clearing every PSC is a deliberate NAICS-only fetch; it must not snap back to the defaults.
    Livewire::test(FetchSamControlPanel::class)
        ->fillForm(['psc_search' => []])
        ->call('executeFetch');

    Livewire::test(FetchSamControlPanel::class)->assertSet('formData.psc_search', []);
});

it('shows per-term match counts and the found-only-via-PSC figure', function () {
    writePscState();

    Livewire::test(FetchSamControlPanel::class)
        ->assertSeeHtml('data-testid="sam-psc-match-count"')
        ->assertSee('53 · 3')
        ->assertSee('59 · 0')
        ->assertSee('332722 · 1')
        ->assertSeeHtml('data-testid="sam-found-only-via-psc"')
        ->assertSeeHtml('data-label="PSC"');
});

it('still counts NAICS matches for results saved before PSC discovery', function () {
    writePscState();
    $state = json_decode(file_get_contents(SamStatePath::opportunities()), true);
    foreach ($state['opportunities'] as &$opp) {
        unset($opp['matched_terms']);
    }
    $state['query']['psc_codes'] = [];
    file_put_contents(SamStatePath::opportunities(), json_encode($state));

    Livewire::test(FetchSamControlPanel::class)
        ->assertSee('332722 · 1')
        ->assertDontSeeHtml('data-testid="sam-found-only-via-psc"');
});

it('caps PSC terms, never NAICS terms, at max_terms_per_fetch', function () {
    config(['sam_opportunities.max_terms_per_fetch' => 3]);

    $resolved = (new SamParameterResolver)->resolve([
        'naics_override' => ['332722', '332510'],
        'psc_search' => ['53', '59', '48'],
    ]);

    expect($resolved['naics_codes'])->toBe(['332722', '332510'])
        ->and($resolved['psc_codes'])->toBe(['53']);
});

it('filters the Excel export by PSC like the results table', function () {
    writePscState();
    Excel::fake();

    $this->get(route('admin.sam-opportunities.export', ['psc' => '5999']))->assertOk();

    Excel::matchByRegex();
    Excel::assertDownloaded('/^sam-opportunities-.*\.xlsx$/', fn ($export) => $export->collection()->pluck('notice_id')->all() === ['c']);
});

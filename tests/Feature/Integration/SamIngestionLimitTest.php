<?php

use App\Exports\SamOpportunitiesStateExport;
use App\Filament\Pages\FetchSamControlPanel;
use App\Jobs\FetchSamOpportunitiesJob;
use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use App\Models\Admin;
use App\Models\SamOpportunity;
use App\Support\SamStatePath;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    config(['services.sam.api_key' => 'fake-key']);
    Storage::fake('local');
    Notification::fake();
    Http::preventStrayRequests();
    $this->path = SamStatePath::opportunities();
    $this->original = is_file($this->path) ? file_get_contents($this->path) : null;
    $this->obLevel = ob_get_level();
    $this->params = ['naics_override' => ['339113'], 'place' => null, 'limit' => 1, 'keywords' => 'Target', 'clearCache' => true];
    $this->records = array_map(fn ($i) => [
        'noticeId' => 'id-'.$i, 'title' => $i < 3 ? 'Target '.$i : 'Unrelated',
        'postedDate' => '2026-09-'.(10 - $i), 'naicsCode' => '339113',
    ], range(0, 3));
});

afterEach(function () {
    if ($this->original !== null) {
        file_put_contents($this->path, $this->original);
    } elseif (is_file($this->path)) {
        unlink($this->path);
    }
    while (ob_get_level() > $this->obLevel) {
        ob_end_clean();
    }
});

it('saves every matching notice while limiting only the panel and direct tool response', function () {
    Http::fake(['*' => Http::response(['totalRecords' => 4, 'opportunitiesData' => $this->records])]);
    $direct = (new FetchSamOpportunitiesTool)->fetch($this->params);
    expect($direct['opportunities'])->toHaveCount(1);

    $job = new FetchSamOpportunitiesJob($this->params);
    $job->handle();
    $job->handle();
    expect(SamOpportunity::pluck('notice_id')->all())->toEqualCanonicalizing(['id-0', 'id-1', 'id-2']);
    $state = json_decode(file_get_contents($this->path), true);
    expect($state['opportunities'])->toHaveCount(3)
        ->and($state['summary']['fetched_count'])->toBe(4)
        ->and($state['summary']['matched_count'])->toBe(3)
        ->and($state['summary']['saved_count'])->toBe(3)
        ->and($state['summary']['displayed_count'])->toBe(1);

    Filament::setCurrentPanel('admin');
    $this->actingAs(Admin::factory()->create(), 'admin');
    Livewire::test(FetchSamControlPanel::class)
        ->assertSet('lastResult.summary.saved_count', 3)
        ->assertSet('lastResult.summary.displayed_count', 1)
        ->assertSet('lastResult.opportunities', [$state['opportunities'][0]])
        ->assertSee('Saved to Feed');

    Excel::fake();
    $this->get(route('admin.sam-opportunities.export', ['q' => 'Target 2']))->assertOk();
    Excel::matchByRegex();
    Excel::assertDownloaded('/^sam-opportunities-.*\.xlsx$/', fn (SamOpportunitiesStateExport $export) => $export->collection()->pluck('notice_id')->all() === ['id-2']
    );
});

it('reports actual saved counts when matches have no notice id', function () {
    $this->records[1]['noticeId'] = null;
    Http::fake(['*' => Http::response(['totalRecords' => 4, 'opportunitiesData' => $this->records])]);
    (new FetchSamOpportunitiesJob($this->params))->handle();
    $summary = json_decode(file_get_contents($this->path), true)['summary'];
    expect(SamOpportunity::count())->toBe(2)
        ->and($summary['matched_count'])->toBe(3)
        ->and($summary['saved_count'])->toBe(2)
        ->and($summary['skipped_count'])->toBe(1);
});

it('records zero saved and displayed when no opportunities match', function () {
    Http::fake(['*' => Http::response(['totalRecords' => 0, 'opportunitiesData' => []])]);
    (new FetchSamOpportunitiesJob($this->params))->handle();
    $summary = json_decode(file_get_contents($this->path), true)['summary'];
    expect(SamOpportunity::count())->toBe(0)
        ->and($summary['saved_count'])->toBe(0)
        ->and($summary['displayed_count'])->toBe(0);
});

it('ingests more than the maximum display limit across API pages', function () {
    Http::fake(function ($request) {
        $start = (int) $request['offset'] * 1000;
        $end = min($start + 1000, 1001);
        $records = $start < $end ? array_map(fn ($i) => [
            'noticeId' => 'page-id-'.$i, 'title' => 'Target '.$i,
        ], range($start, $end - 1)) : [];

        return Http::response(['totalRecords' => 1001, 'opportunitiesData' => $records]);
    });
    (new FetchSamOpportunitiesJob($this->params))->handle();

    expect(SamOpportunity::count())->toBe(1001)
        ->and(SamOpportunity::where('notice_id', 'page-id-1000')->exists())->toBeTrue();
    $state = json_decode(file_get_contents($this->path), true);
    expect($state['opportunities'])->toHaveCount(1001)
        ->and($state['summary']['saved_count'])->toBe(1001)
        ->and($state['summary']['displayed_count'])->toBe(1);
    Http::assertSentCount(2);
});

<?php

use App\Filament\Pages\FetchSamControlPanel;
use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use App\Models\Admin;
use App\Support\SamStatePath;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.sam.api_key' => 'fake-key', 'services.sam.base_url' => 'https://sam.test/search', 'services.sam.max_pages' => 10]);
    Cache::flush();
    Storage::fake('local');
    Http::preventStrayRequests();
    $this->statePath = SamStatePath::opportunities();
    $this->originalState = is_file($this->statePath) ? file_get_contents($this->statePath) : null;
    $this->obLevel = ob_get_level();
    $this->params = ['naics_override' => ['339113'], 'place' => null, 'days_back' => 30, 'limit' => 1000];
});

afterEach(function () {
    if ($this->originalState !== null) {
        file_put_contents($this->statePath, $this->originalState);
    } elseif (is_file($this->statePath)) {
        unlink($this->statePath);
    }
    while (ob_get_level() > $this->obLevel) {
        ob_end_clean();
    }
});

it('reports incomplete pages through the tool and retries rather than caching them', function (string $ending) {
    if ($ending === 'cap') {
        config(['services.sam.max_pages' => 1]);
    }
    Http::fake(function ($request) use ($ending) {
        if ((int) $request['offset'] === 0) {
            return Http::response(['totalRecords' => 2500, 'opportunitiesData' => array_map(
                fn ($i) => ['noticeId' => 'notice-'.$i, 'title' => 'Opportunity '.$i], range(0, 999)
            )]);
        }

        return match ($ending) {
            'http_error' => Http::response('Server error', 500),
            'no_data' => Http::response(['message' => 'No records found'], 404),
            default => Http::response(['totalRecords' => 2500, 'opportunitiesData' => []]),
        };
    });

    foreach (range(1, 2) as $attempt) {
        $result = (new FetchSamOpportunitiesTool)->fetch($this->params);
        expect($result['success'])->toBeFalse()
            ->and($result['partial_success'])->toBeTrue()
            ->and($result['opportunities'])->toHaveCount(1000)
            ->and($result['summary']['incomplete_naics_count'])->toBe(1)
            ->and($result['summary']['api_total_records'])->toBe(2500)
            ->and($result['summary']['total_records'])->toBe(1000)
            ->and($result['summary']['failed_naics'][0]['type'])->toBe('incomplete_results')
            ->and($result['performance']['cache_hits'])->toBe(0);
    }

    Http::assertSentCount($ending === 'cap' ? 2 : 4);
    $saved = json_decode(file_get_contents($this->statePath), true);
    expect($saved['partial_success'])->toBeTrue()
        ->and($saved['summary']['api_total_records'])->toBe(2500);
})->with(['empty', 'http_error', 'no_data', 'cap']);

it('finds matches on the last page and reuses a complete cached fetch', function () {
    Http::fake(function ($request) {
        $start = (int) $request['offset'] * (int) $request['limit'];
        $end = min($start + (int) $request['limit'], 2500);
        $records = $start < $end ? array_map(
            fn ($i) => ['noticeId' => 'notice-'.$i, 'title' => $i === 2499 ? 'Target match' : 'Other'],
            range($start, $end - 1)
        ) : [];

        return Http::response(['totalRecords' => 2500, 'opportunitiesData' => $records]);
    });
    foreach (range(0, 1) as $cacheHits) {
        $result = (new FetchSamOpportunitiesTool)->fetch($this->params + ['keywords' => 'Target']);
        expect($result['success'])->toBeTrue()
            ->and($result['partial_success'])->toBeFalse()
            ->and(array_column($result['opportunities'], 'notice_id'))->toBe(['notice-2499'])
            ->and($result['summary']['total_records'])->toBe(2500)
            ->and($result['summary']['incomplete_naics_count'])->toBe(0)
            ->and($result['performance']['cache_hits'])->toBe($cacheHits);
    }
    Http::assertSentCount(3);
});

it('shows incomplete retrieval in the panel even when filters leave no results', function () {
    config(['services.sam.max_pages' => 1]);
    Http::fake(['*' => Http::response([
        'totalRecords' => 2500, 'opportunitiesData' => [['noticeId' => 'a', 'title' => 'Other']],
    ])]);
    $result = (new FetchSamOpportunitiesTool)->fetch($this->params + ['keywords' => 'Target']);
    expect($result['opportunities'])->toBe([])->and($result['partial_success'])->toBeTrue();

    Filament::setCurrentPanel('admin');
    $this->actingAs(Admin::factory()->create(), 'admin');
    Livewire::test(FetchSamControlPanel::class)
        ->assertSee('Some matching opportunities could not be retrieved')
        ->assertSee('2,500')
        ->assertSee('NAICS Query Warnings');
});

<?php

use App\Filament\Pages\FetchSamControlPanel;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->initialObLevel = ob_get_level();

    $this->actingAs(Admin::factory()->create(), 'admin');

    $result = [
        'fetched_at' => now()->toIso8601String(),
        'summary' => [
            'total_records' => 1,
            'total_after_dedup' => 1,
            'returned' => 1,
            'limit' => 1000,
            'cache_hit_rate' => '0%',
        ],
        'opportunities' => [
            [
                'title' => 'Test Opportunity',
                'sam_url' => 'https://sam.gov',
                'solicitation_number' => 'ABC-123',
                'notice_type' => 'Solicitation',
                'posted_date' => '2025-01-01',
                'response_deadline' => '2025-02-01',
                'naics_code' => '541330',
                'set_aside_type' => 'Small Business',
                'agency_name' => 'Test Agency',
                'state_code' => 'CO',
            ],
        ],
    ];

    $path = app_path('Mcp/Servers/Business/State/sam-opportunities.json');
    $dir = dirname($path);

    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($path, json_encode($result, JSON_PRETTY_PRINT));
});

afterEach(function () {
    $path = app_path('Mcp/Servers/Business/State/sam-opportunities.json');

    if (file_exists($path)) {
        unlink($path);
    }
    $initialObLevel = $this->initialObLevel ?? 0;

    while (ob_get_level() > $initialObLevel) {
        ob_end_clean();
    }
});

it('renders the mobile naics row in the opportunities table', function () {
    Livewire::test(FetchSamControlPanel::class)
        ->assertSeeHtml('data-testid="sam-naics-mobile"')
        ->assertSee('541330');
});

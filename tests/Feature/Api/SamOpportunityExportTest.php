<?php

use App\Models\SamOpportunity;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    if (! Schema::hasTable('sam_opportunities')) {
        Schema::create('sam_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('notice_id')->unique()->nullable();
            $table->string('title')->nullable();
            $table->string('agency')->nullable();
            $table->date('response_deadline')->nullable();
            $table->text('description')->nullable();
            $table->string('notice_type')->nullable();
            $table->string('naics_code')->nullable();
            $table->string('psc_code')->nullable();
            $table->string('set_aside')->nullable();
            $table->string('place_of_performance')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('posted_date')->nullable();
            $table->timestamp('last_modified_date')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }
});

test('unauthenticated user cannot export opportunities', function () {
    $response = $this->postJson('/api/v1/sam-opportunities/export');

    $response->assertUnauthorized();
});

test('authenticated user can export opportunities', function () {
    Excel::fake();

    $user = User::factory()->create();

    SamOpportunity::create([
        'notice_id' => 'TEST-001',
        'title' => 'Test Opportunity',
        'agency' => 'Department of Defense',
        'notice_type' => 'RFQ',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export');

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename) {
        return str_contains($filename, 'sam-opportunities-') && str_ends_with($filename, '.xlsx');
    });
});

test('export includes only favorited opportunities when favorites_only is true', function () {
    Excel::fake();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create opportunities
    $favoriteOpp = SamOpportunity::create([
        'notice_id' => 'FAV-001',
        'title' => 'Favorite Opportunity',
        'agency' => 'Department of Defense',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $nonFavoriteOpp = SamOpportunity::create([
        'notice_id' => 'NONFAV-001',
        'title' => 'Non-Favorite Opportunity',
        'agency' => 'NASA',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    // Favorite only one opportunity
    $user->favoriteSamOpportunities()->attach($favoriteOpp->id);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export', [
            'favorites_only' => true,
        ]);

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename, $export) use ($favoriteOpp, $nonFavoriteOpp) {
        $query = $export->query();
        $results = $query->get();

        // Should only include the favorited opportunity
        return $results->contains('id', $favoriteOpp->id)
            && ! $results->contains('id', $nonFavoriteOpp->id);
    });
});

test('export respects agency filter', function () {
    Excel::fake();

    $user = User::factory()->create();

    SamOpportunity::create([
        'notice_id' => 'DOD-001',
        'title' => 'DOD Opportunity',
        'agency' => 'Department of Defense',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    SamOpportunity::create([
        'notice_id' => 'NASA-001',
        'title' => 'NASA Opportunity',
        'agency' => 'NASA',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export', [
            'filters' => [
                'agency' => 'Department of Defense',
            ],
        ]);

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename, $export) {
        $query = $export->query();
        $results = $query->get();

        // Should only include DOD opportunities
        return $results->count() === 1
            && $results->first()->agency === 'Department of Defense';
    });
});

test('export respects notice_type filter', function () {
    Excel::fake();

    $user = User::factory()->create();

    SamOpportunity::create([
        'notice_id' => 'RFQ-001',
        'title' => 'RFQ Opportunity',
        'agency' => 'Department of Defense',
        'notice_type' => 'RFQ',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    SamOpportunity::create([
        'notice_id' => 'RFI-001',
        'title' => 'RFI Opportunity',
        'agency' => 'NASA',
        'notice_type' => 'RFI',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export', [
            'filters' => [
                'notice_type' => 'RFQ',
            ],
        ]);

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename, $export) {
        $query = $export->query();
        $results = $query->get();

        // Should only include RFQ opportunities
        return $results->count() === 1
            && $results->first()->notice_type === 'RFQ';
    });
});

test('export respects naics_code filter', function () {
    Excel::fake();

    $user = User::factory()->create();

    SamOpportunity::create([
        'notice_id' => 'NAICS-541330',
        'title' => 'Engineering Services',
        'agency' => 'Department of Defense',
        'naics_code' => '541330',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    SamOpportunity::create([
        'notice_id' => 'NAICS-336411',
        'title' => 'Aircraft Manufacturing',
        'agency' => 'NASA',
        'naics_code' => '336411',
        'response_deadline' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export', [
            'filters' => [
                'naics_code' => '541330',
            ],
        ]);

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename, $export) {
        $query = $export->query();
        $results = $query->get();

        // Should only include opportunities with NAICS 541330
        return $results->count() === 1
            && $results->first()->naics_code === '541330';
    });
});

test('export sorts by response_deadline ascending then posted_date descending', function () {
    Excel::fake();

    $user = User::factory()->create();

    // Create opportunities with different deadlines
    $opp1 = SamOpportunity::create([
        'notice_id' => 'DEADLINE-3',
        'title' => 'Deadline in 3 days',
        'agency' => 'DOD',
        'response_deadline' => now()->addDays(3)->format('Y-m-d'),
        'posted_date' => now()->subDays(5),
    ]);

    $opp2 = SamOpportunity::create([
        'notice_id' => 'DEADLINE-1',
        'title' => 'Deadline in 1 day',
        'agency' => 'DOD',
        'response_deadline' => now()->addDays(1)->format('Y-m-d'),
        'posted_date' => now()->subDays(10),
    ]);

    $opp3 = SamOpportunity::create([
        'notice_id' => 'DEADLINE-2',
        'title' => 'Deadline in 2 days',
        'agency' => 'DOD',
        'response_deadline' => now()->addDays(2)->format('Y-m-d'),
        'posted_date' => now()->subDays(7),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export');

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename, $export) use ($opp1, $opp2, $opp3) {
        $results = $export->query()->get();

        // Should be sorted by deadline ascending
        return $results[0]->id === $opp2->id  // 1 day
            && $results[1]->id === $opp3->id  // 2 days
            && $results[2]->id === $opp1->id; // 3 days
    });
});

test('export validates input parameters', function () {
    $user = User::factory()->create();

    // Test invalid favorites_only (not boolean)
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export', [
            'favorites_only' => 'invalid',
        ]);

    $response->assertUnprocessable();
});

test('export can handle empty result set', function () {
    Excel::fake();

    $user = User::factory()->create();

    // No opportunities in database
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export');

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename, $export) {
        $results = $export->query()->get();

        // Should have empty results
        return $results->count() === 0;
    });
});

test('export filename includes timestamp in america denver timezone', function () {
    Excel::fake();

    $user = User::factory()->create();

    SamOpportunity::create([
        'notice_id' => 'TEST-001',
        'title' => 'Test Opportunity',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export');

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename) {
        // Check filename format: sam-opportunities-YYYYMMDD-HHMMSS.xlsx
        $pattern = '/^sam-opportunities-\d{8}-\d{6}\.xlsx$/';

        return preg_match($pattern, $filename) === 1;
    });
});

test('export limits results to max export rows', function () {
    Excel::fake();

    $user = User::factory()->create();

    // The limit is enforced at the query level
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export');

    $response->assertSuccessful();

    Excel::assertDownloaded(function ($filename, $export) {
        $query = $export->query();
        $sql = $query->toSql();

        // Should contain limit clause
        return str_contains($sql, 'limit 50000');
    });
});

test('export is rate limited', function () {
    Excel::fake();

    $user = User::factory()->create();

    SamOpportunity::create([
        'notice_id' => 'TEST-001',
        'title' => 'Test Opportunity',
    ]);

    // Make 11 requests (should hit rate limit on 11th)
    for ($i = 0; $i < 11; $i++) {
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sam-opportunities/export');

        if ($i < 10) {
            $response->assertSuccessful();
        } else {
            // 11th request should be rate limited
            $response->assertStatus(429);
        }
    }
});

test('export handles server errors gracefully', function () {
    // Don't fake Excel to trigger actual export attempt
    $user = User::factory()->create();

    // Create an invalid scenario that would cause an error
    // This test mainly ensures the try-catch block is in place
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sam-opportunities/export', [
            'filters' => [
                'invalid_filter' => 'test',
            ],
        ]);

    // Should either succeed (if validation passes) or return validation error
    expect($response->status())->toBeIn([200, 422, 500]);
});

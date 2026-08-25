<?php

declare(strict_types=1);

use App\Jobs\FetchSamOpportunitiesJob;
use App\Models\SamOpportunity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Database persistence regression tests
|--------------------------------------------------------------------------
|
| The job used to write columns that do not exist on `sam_opportunities`
| (`department`, `classification_code`, `active`, `type`, `links`). Every
| insert threw, the throw was swallowed by a catch-all, and the job still
| logged "completed" — so the table stayed empty while the fetch reported
| success. The pre-existing "job executes and persists results" test only
| asserted on the state file, so it never caught this.
|
| These tests assert on the database.
|
*/

function fakeSamResponse(array $overrides = []): array
{
    return array_merge([
        'noticeId' => 'notice-001',
        'solicitationNumber' => 'SPE8EF-26-T-0001',
        'title' => 'Industrial Fasteners — Fort Carson',
        'type' => 'Combined Synopsis/Solicitation',
        'postedDate' => '2026-08-01',
        'responseDeadLine' => '2026-08-30',
        'naics' => '423840',
        'psc' => '5340',
        'typeOfSetAsideDescription' => 'Total Small Business Set-Aside',
        'uiLink' => 'https://sam.gov/opp/notice-001/view',
        'lastModifiedDate' => '2026-08-02T12:00:00-04:00',
        'description' => 'https://api.sam.gov/prod/opportunities/v1/noticedesc?noticeid=notice-001',
        'fullParentPathName' => 'DEPT OF DEFENSE',
        'placeOfPerformance' => ['state' => ['code' => 'CO']],
    ], $overrides);
}

it('writes fetched opportunities to the database with the real schema columns', function () {
    Config::set('services.sam.api_key', 'test-api-key');

    Http::fake([
        '*' => Http::response([
            'opportunitiesData' => [fakeSamResponse()],
            'totalRecords' => 1,
        ], 200),
    ]);

    (new FetchSamOpportunitiesJob(
        params: ['naics_override' => ['423840'], 'days_back' => 7, 'limit' => 100],
        userId: null
    ))->handle();

    expect(SamOpportunity::count())->toBe(1);

    $opp = SamOpportunity::first();

    expect($opp->notice_id)->toBe('notice-001')
        ->and($opp->solicitation_number)->toBe('SPE8EF-26-T-0001')
        ->and($opp->title)->toBe('Industrial Fasteners — Fort Carson')
        ->and($opp->agency)->toBe('DEPT OF DEFENSE')
        ->and($opp->naics_code)->toBe('423840')
        ->and($opp->psc_code)->toBe('5340')
        ->and($opp->notice_type)->toBe('Combined Synopsis/Solicitation')
        ->and($opp->set_aside)->toBe('Total Small Business Set-Aside')
        ->and($opp->place_of_performance)->toBe('CO')
        ->and($opp->url)->toBe('https://sam.gov/opp/notice-001/view')
        ->and($opp->description)->not->toBeNull()
        ->and($opp->posted_date)->not->toBeNull()
        ->and($opp->response_deadline)->not->toBeNull()
        ->and($opp->last_modified_date)->not->toBeNull();
});

it('updates rather than duplicates when the same notice is fetched twice', function () {
    Config::set('services.sam.api_key', 'test-api-key');

    // A second Http::fake() call does not replace an earlier matching stub, so
    // sequence the two responses instead.
    Http::fakeSequence()
        ->push(['opportunitiesData' => [fakeSamResponse(['title' => 'Original Title'])], 'totalRecords' => 1], 200)
        ->push(['opportunitiesData' => [fakeSamResponse(['title' => 'Amended Title'])], 'totalRecords' => 1], 200);

    $params = ['naics_override' => ['423840'], 'days_back' => 7, 'limit' => 100, 'clearCache' => true];

    (new FetchSamOpportunitiesJob(params: $params))->handle();
    (new FetchSamOpportunitiesJob(params: $params))->handle();

    expect(SamOpportunity::count())->toBe(1)
        ->and(SamOpportunity::first()->title)->toBe('Amended Title');
});

it('fails loudly instead of silently when the database write fails', function () {
    Config::set('services.sam.api_key', 'test-api-key');

    Http::fake([
        '*' => Http::response([
            'opportunitiesData' => [fakeSamResponse()],
            'totalRecords' => 1,
        ], 200),
    ]);

    // Simulate the exact historical failure mode: the table is unusable.
    DB::statement('DROP TABLE sam_opportunities');

    $job = new FetchSamOpportunitiesJob(
        params: ['naics_override' => ['423840'], 'days_back' => 7, 'limit' => 100],
    );

    expect(fn () => $job->handle())->toThrow(QueryException::class);
});

it('skips records with no notice_id rather than aborting the batch', function () {
    Config::set('services.sam.api_key', 'test-api-key');

    Http::fake([
        '*' => Http::response([
            'opportunitiesData' => [
                fakeSamResponse(['noticeId' => null, 'title' => 'No Notice Id']),
                fakeSamResponse(['noticeId' => 'notice-002', 'title' => 'Has Notice Id']),
            ],
            'totalRecords' => 2,
        ], 200),
    ]);

    (new FetchSamOpportunitiesJob(
        params: ['naics_override' => ['423840'], 'days_back' => 7, 'limit' => 100],
    ))->handle();

    expect(SamOpportunity::count())->toBe(1)
        ->and(SamOpportunity::first()->notice_id)->toBe('notice-002');
});

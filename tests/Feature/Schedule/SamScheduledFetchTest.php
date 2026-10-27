<?php

declare(strict_types=1);

use App\Jobs\FetchSamOpportunitiesJob;
use App\Support\SamSearchParameters;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;

/*
|--------------------------------------------------------------------------
| Scheduled SAM fetch regression test
|--------------------------------------------------------------------------
|
| The scheduled task used to call FetchSamOpportunitiesTool::handle([...])
| statically. handle() is an instance method typed
| handle(Request $request): Response, so every nightly run threw
| "Non-static method ... cannot be called statically". Error does not extend
| Exception, so the surrounding catch (Exception $e) never caught it and the
| daily 06:00 fetch failed silently from the day it was written.
|
| This runs the real registered schedule callback, which is the only way to
| catch that class of bug.
|
| Deliberately a single test: Laravel's withSchedule() callback registers only
| for the first test in a process, so a second test resolving Schedule sees an
| incomplete event list. Splitting these assertions makes the file order-
| dependent and flaky.
|
*/

it('registers a daily SAM fetch that runs without throwing and queues the job', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'fetch-sam-opportunities');

    expect($event)->not->toBeNull('The fetch-sam-opportunities scheduled task is not registered.')
        ->and($event->expression)->toBe('0 6 * * *')
        ->and($event->timezone)->toBe('America/Denver'); // 6am Mountain, not 6am UTC

    Queue::fake();

    // A static-call Error would surface here, exactly as it did nightly in
    // production. Errors are not caught by the task's guard, so this throws.
    $event->run(app());

    // NAICS OR PSC discovery (ai/modules/sam-psc-discovery): the daily fetch
    // searches the DLA groups and 4-character PSCs as well.
    Queue::assertPushed(
        FetchSamOpportunitiesJob::class,
        fn (FetchSamOpportunitiesJob $job) => ($job->params['psc_search'] ?? []) === (new SamSearchParameters)->defaultPscSearch()
            && in_array('53', $job->params['psc_search'], true)
            && in_array('5340', $job->params['psc_search'], true),
    );
});

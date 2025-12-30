<?php

declare(strict_types=1);

use App\Support\SamStateFileManager;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Use a test disk to avoid polluting production storage
    Storage::fake('local');
});

describe('saving state', function () {
    test('saves state to JSON file', function () {
        $manager = new SamStateFileManager;

        $params = ['posted_from' => '01/01/2025', 'posted_to' => '01/31/2025'];
        $summary = ['total_opportunities' => 100, 'naics_queried' => 5];
        $failedNaics = [['naics' => '123456', 'error' => 'Timeout']];

        $path = $manager->save($params, $summary, $failedNaics);

        expect($path)->toContain('sam/state/sam_state_')
            ->and(Storage::disk('local')->exists($path))->toBeTrue();
    });

    test('saved file contains correct structure', function () {
        $manager = new SamStateFileManager;

        $params = ['posted_from' => '01/01/2025'];
        $summary = ['total_opportunities' => 50];
        $failedNaics = [];

        $path = $manager->save($params, $summary, $failedNaics);
        $contents = Storage::disk('local')->get($path);
        $data = json_decode($contents, true);

        expect($data)->toHaveKeys(['timestamp', 'params', 'summary', 'failed_naics'])
            ->and($data['params'])->toBe($params)
            ->and($data['summary'])->toBe($summary)
            ->and($data['failed_naics'])->toBe($failedNaics)
            ->and($data['timestamp'])->toBeString();
    });

    test('saves file with timestamped filename', function () {
        $manager = new SamStateFileManager;

        $path = $manager->save([], []);

        expect($path)->toMatch('/sam_state_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}-\d{6}\.json$/');
    });

    test('creates directory if it does not exist', function () {
        $manager = new SamStateFileManager;

        expect(Storage::disk('local')->exists('sam/state'))->toBeFalse();

        $manager->save([], []);

        expect(Storage::disk('local')->exists('sam/state'))->toBeTrue();
    });
});

describe('loading state', function () {
    test('loads latest state file', function () {
        $manager = new SamStateFileManager;

        $params1 = ['posted_from' => '01/01/2025'];
        $summary1 = ['total_opportunities' => 50];

        $manager->save($params1, $summary1);

        sleep(1); // Ensure different timestamp

        $params2 = ['posted_from' => '02/01/2025'];
        $summary2 = ['total_opportunities' => 75];

        $manager->save($params2, $summary2);

        $latest = $manager->loadLatest();

        expect($latest['params'])->toBe($params2)
            ->and($latest['summary'])->toBe($summary2);
    });

    test('returns null when no state files exist', function () {
        $manager = new SamStateFileManager;

        $latest = $manager->loadLatest();

        expect($latest)->toBeNull();
    });

    test('loads specific state file by filename', function () {
        $manager = new SamStateFileManager;

        $params = ['posted_from' => '03/01/2025'];
        $summary = ['total_opportunities' => 100];

        $path = $manager->save($params, $summary);
        $filename = basename($path);

        $loaded = $manager->load($filename);

        expect($loaded['params'])->toBe($params)
            ->and($loaded['summary'])->toBe($summary);
    });

    test('returns null when loading non-existent file', function () {
        $manager = new SamStateFileManager;

        $loaded = $manager->load('non_existent_file.json');

        expect($loaded)->toBeNull();
    });
});

describe('listing state files', function () {
    test('gets all state files sorted by modified time', function () {
        $manager = new SamStateFileManager;

        $manager->save(['v' => 1], ['count' => 1]);
        sleep(1);
        $manager->save(['v' => 2], ['count' => 2]);
        sleep(1);
        $manager->save(['v' => 3], ['count' => 3]);

        $files = $manager->getStateFiles();

        expect($files)->toHaveCount(3)
            ->and($files[0])->toContain('sam_state_') // Newest first
            ->and($files[2])->toContain('sam_state_'); // Oldest last
    });

    test('returns empty array when no state files exist', function () {
        $manager = new SamStateFileManager;

        $files = $manager->getStateFiles();

        expect($files)->toBeEmpty();
    });

    test('filters only JSON files', function () {
        $manager = new SamStateFileManager;

        // Create state file
        $manager->save([], []);

        // Create non-JSON file in same directory
        Storage::disk('local')->put('sam/state/not_a_state.txt', 'test');

        $files = $manager->getStateFiles();

        expect($files)->toHaveCount(1)
            ->and($files[0])->toEndWith('.json');
    });
});

describe('rotating state files', function () {
    test('keeps only N most recent files', function () {
        $manager = new SamStateFileManager;

        // Create 5 state files
        for ($i = 1; $i <= 5; $i++) {
            $manager->save(['version' => $i], ['count' => $i * 10]);
            usleep(100000); // 100ms delay
        }

        expect($manager->count())->toBe(5);

        // Keep only 3 most recent
        $deleted = $manager->rotate(3);

        expect($deleted)->toBe(2)
            ->and($manager->count())->toBe(3);
    });

    test('does not delete files when count is below threshold', function () {
        $manager = new SamStateFileManager;

        $manager->save([], []);
        $manager->save([], []);

        $deleted = $manager->rotate(10);

        expect($deleted)->toBe(0)
            ->and($manager->count())->toBe(2);
    });

    test('uses default keep count when not specified', function () {
        $manager = new SamStateFileManager;

        // Create 15 files (default keep count is 10)
        for ($i = 1; $i <= 15; $i++) {
            $manager->save(['v' => $i], []);
            usleep(50000); // 50ms delay
        }

        $deleted = $manager->rotate();

        expect($deleted)->toBe(5)
            ->and($manager->count())->toBe(10);
    });
});

describe('clearing state', function () {
    test('deletes all state files', function () {
        $manager = new SamStateFileManager;

        $manager->save([], []);
        $manager->save([], []);
        $manager->save([], []);

        expect($manager->count())->toBe(3);

        $deleted = $manager->clear();

        expect($deleted)->toBe(3)
            ->and($manager->count())->toBe(0);
    });

    test('returns zero when no files to delete', function () {
        $manager = new SamStateFileManager;

        $deleted = $manager->clear();

        expect($deleted)->toBe(0);
    });
});

describe('latest state helpers', function () {
    test('gets latest summary', function () {
        $manager = new SamStateFileManager;

        $summary = ['total_opportunities' => 150, 'cache_hits' => 10];

        $manager->save([], $summary);

        $latestSummary = $manager->getLatestSummary();

        expect($latestSummary)->toBe($summary);
    });

    test('returns null for latest summary when no state exists', function () {
        $manager = new SamStateFileManager;

        $latestSummary = $manager->getLatestSummary();

        expect($latestSummary)->toBeNull();
    });

    test('gets latest timestamp', function () {
        $manager = new SamStateFileManager;

        $manager->save([], []);

        $timestamp = $manager->getLatestTimestamp();

        expect($timestamp)->toBeString()
            ->and($timestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });

    test('returns null for latest timestamp when no state exists', function () {
        $manager = new SamStateFileManager;

        $timestamp = $manager->getLatestTimestamp();

        expect($timestamp)->toBeNull();
    });

    test('gets latest failed NAICS', function () {
        $manager = new SamStateFileManager;

        $failedNaics = [
            ['naics' => '123456', 'error' => 'Timeout'],
            ['naics' => '789012', 'error' => 'Rate limit'],
        ];

        $manager->save([], [], $failedNaics);

        $latest = $manager->getLatestFailedNaics();

        expect($latest)->toBe($failedNaics);
    });

    test('returns empty array for failed NAICS when no state exists', function () {
        $manager = new SamStateFileManager;

        $failed = $manager->getLatestFailedNaics();

        expect($failed)->toBe([]);
    });
});

describe('state checking', function () {
    test('hasState returns true when state files exist', function () {
        $manager = new SamStateFileManager;

        $manager->save([], []);

        expect($manager->hasState())->toBeTrue();
    });

    test('hasState returns false when no state files exist', function () {
        $manager = new SamStateFileManager;

        expect($manager->hasState())->toBeFalse();
    });

    test('count returns correct number of state files', function () {
        $manager = new SamStateFileManager;

        expect($manager->count())->toBe(0);

        $manager->save([], []);
        $manager->save([], []);

        expect($manager->count())->toBe(2);
    });
});

describe('all state data', function () {
    test('returns all state data with metadata', function () {
        $manager = new SamStateFileManager;

        $manager->save(['v' => 1], ['count' => 10]);
        sleep(1);
        $manager->save(['v' => 2], ['count' => 20]);

        $all = $manager->all();

        expect($all)->toHaveCount(2)
            ->and($all[0])->toHaveKeys(['timestamp', 'params', 'summary', 'failed_naics', '_file', '_modified', '_size'])
            ->and($all[0]['params']['v'])->toBe(2) // Newest first
            ->and($all[1]['params']['v'])->toBe(1); // Oldest last
    });

    test('returns empty array when no state exists', function () {
        $manager = new SamStateFileManager;

        $all = $manager->all();

        expect($all)->toBeEmpty();
    });
});

describe('custom disk', function () {
    test('uses custom disk when specified', function () {
        Storage::fake('test-disk');

        $manager = new SamStateFileManager('test-disk');

        $manager->save(['custom' => true], []);

        expect(Storage::disk('test-disk')->exists('sam/state'))->toBeTrue()
            ->and($manager->count())->toBe(1);
    });
});

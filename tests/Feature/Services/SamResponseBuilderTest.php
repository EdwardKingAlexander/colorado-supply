<?php

declare(strict_types=1);

use App\Support\SamResponseBuilder;

describe('success response', function () {
    test('builds success response with opportunities and metadata', function () {
        $builder = new SamResponseBuilder;

        $opportunities = [
            ['notice_id' => '12345', 'title' => 'Test Opportunity 1'],
            ['notice_id' => '67890', 'title' => 'Test Opportunity 2'],
        ];

        $metadata = [
            'naics_queried' => 3,
            'naics_succeeded' => 3,
            'naics_failed' => 0,
            'cache_hits' => 2,
            'cache_misses' => 1,
            'total_duration_ms' => 1500,
        ];

        $response = $builder->success($opportunities, $metadata);

        expect($response['status'])->toBe('success')
            ->and($response['opportunities'])->toHaveCount(2)
            ->and($response['metadata']['total_count'])->toBe(2)
            ->and($response['metadata']['naics_queried'])->toBe(3)
            ->and($response['metadata']['cache_hits'])->toBe(2)
            ->and($response)->not->toHaveKey('errors');
    });

    test('adds total_count to metadata automatically', function () {
        $builder = new SamResponseBuilder;

        $opportunities = [
            ['notice_id' => '1'],
            ['notice_id' => '2'],
            ['notice_id' => '3'],
        ];

        $response = $builder->success($opportunities);

        expect($response['metadata']['total_count'])->toBe(3);
    });
});

describe('partial success response', function () {
    test('builds partial success response with errors', function () {
        $builder = new SamResponseBuilder;

        $opportunities = [
            ['notice_id' => '12345', 'title' => 'Test Opportunity'],
        ];

        $metadata = [
            'naics_queried' => 3,
            'naics_succeeded' => 2,
            'naics_failed' => 1,
        ];

        $errors = [
            ['message' => 'NAICS 123456 timed out', 'naics' => '123456', 'type' => 'timeout'],
        ];

        $response = $builder->partialSuccess($opportunities, $metadata, $errors);

        expect($response['status'])->toBe('partial_success')
            ->and($response['opportunities'])->toHaveCount(1)
            ->and($response['metadata']['total_count'])->toBe(1)
            ->and($response['metadata']['errors_count'])->toBe(1)
            ->and($response['errors'])->toHaveCount(1)
            ->and($response['errors'][0]['message'])->toBe('NAICS 123456 timed out')
            ->and($response['errors'][0]['naics'])->toBe('123456');
    });

    test('formats string errors correctly', function () {
        $builder = new SamResponseBuilder;

        $errors = ['Rate limit exceeded'];

        $response = $builder->partialSuccess([], [], $errors);

        expect($response['errors'])->toHaveCount(1)
            ->and($response['errors'][0]['message'])->toBe('Rate limit exceeded');
    });
});

describe('failure response', function () {
    test('builds failure response with primary error', function () {
        $builder = new SamResponseBuilder;

        $metadata = [
            'naics_queried' => 3,
            'naics_succeeded' => 0,
            'naics_failed' => 3,
        ];

        $errors = [
            ['message' => 'API key invalid', 'type' => 'authentication'],
            ['message' => 'NAICS 123456 failed', 'naics' => '123456'],
        ];

        $response = $builder->failure('All NAICS queries failed', $metadata, $errors);

        expect($response['status'])->toBe('failure')
            ->and($response['error'])->toBe('All NAICS queries failed')
            ->and($response['metadata']['total_count'])->toBe(0)
            ->and($response['errors'])->toHaveCount(3) // Primary + additional
            ->and($response['errors'][0]['message'])->toBe('All NAICS queries failed');
    });

    test('failure response has no opportunities key', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->failure('Failed');

        expect($response)->not->toHaveKey('opportunities');
    });
});

describe('determine status', function () {
    test('determines success when no failures', function () {
        $builder = new SamResponseBuilder;

        $status = $builder->determineStatus(succeeded: 5, failed: 0);

        expect($status)->toBe('success');
    });

    test('determines partial success when some succeed', function () {
        $builder = new SamResponseBuilder;

        $status = $builder->determineStatus(succeeded: 3, failed: 2);

        expect($status)->toBe('partial_success');
    });

    test('determines failure when all fail', function () {
        $builder = new SamResponseBuilder;

        $status = $builder->determineStatus(succeeded: 0, failed: 5);

        expect($status)->toBe('failure');
    });
});

describe('auto build', function () {
    test('auto builds success response', function () {
        $builder = new SamResponseBuilder;

        $opportunities = [['notice_id' => '1']];
        $metadata = ['naics_succeeded' => 1, 'naics_failed' => 0];

        $response = $builder->build($opportunities, $metadata);

        expect($response['status'])->toBe('success');
    });

    test('auto builds partial success response', function () {
        $builder = new SamResponseBuilder;

        $opportunities = [['notice_id' => '1']];
        $metadata = ['naics_succeeded' => 1, 'naics_failed' => 1];
        $errors = [['message' => 'One failed']];

        $response = $builder->build($opportunities, $metadata, $errors);

        expect($response['status'])->toBe('partial_success')
            ->and($response['errors'])->toHaveCount(1);
    });

    test('auto builds failure response', function () {
        $builder = new SamResponseBuilder;

        $opportunities = [];
        $metadata = ['naics_succeeded' => 0, 'naics_failed' => 3];
        $errors = [['message' => 'All failed']];

        $response = $builder->build($opportunities, $metadata, $errors);

        expect($response['status'])->toBe('failure')
            ->and($response['error'])->toBe('All failed');
    });

    test('handles missing metadata gracefully', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->build([]);

        // With no metadata, naics_succeeded and naics_failed both default to 0
        // failed === 0 means success
        expect($response['status'])->toBe('success')
            ->and($response['metadata']['naics_succeeded'])->toBe(0)
            ->and($response['metadata']['naics_failed'])->toBe(0);
    });
});

describe('extract errors', function () {
    test('extracts errors from fetch results', function () {
        $builder = new SamResponseBuilder;

        $fetchResults = [
            ['success' => true, 'naics' => '111111'],
            ['success' => false, 'naics' => '222222', 'error' => 'Timeout', 'error_type' => 'timeout'],
            ['success' => true, 'naics' => '333333'],
            ['success' => false, 'naics' => '444444', 'error' => 'Rate limit', 'error_type' => 'rate_limit'],
        ];

        $errors = $builder->extractErrors($fetchResults);

        expect($errors)->toHaveCount(2)
            ->and($errors[0]['message'])->toBe('Timeout')
            ->and($errors[0]['naics'])->toBe('222222')
            ->and($errors[0]['type'])->toBe('timeout')
            ->and($errors[1]['message'])->toBe('Rate limit')
            ->and($errors[1]['naics'])->toBe('444444');
    });

    test('returns empty array when all succeed', function () {
        $builder = new SamResponseBuilder;

        $fetchResults = [
            ['success' => true, 'naics' => '111111'],
            ['success' => true, 'naics' => '222222'],
        ];

        $errors = $builder->extractErrors($fetchResults);

        expect($errors)->toBeEmpty();
    });
});

describe('add warnings', function () {
    test('adds warnings to metadata', function () {
        $builder = new SamResponseBuilder;

        $metadata = ['cache_hits' => 5];
        $warnings = ['Low cache hit rate', 'Slow queries detected'];

        $result = $builder->addWarnings($metadata, $warnings);

        expect($result['warnings'])->toBe($warnings)
            ->and($result['warnings_count'])->toBe(2)
            ->and($result['cache_hits'])->toBe(5); // Original metadata preserved
    });

    test('does not add warnings when empty', function () {
        $builder = new SamResponseBuilder;

        $metadata = ['cache_hits' => 5];

        $result = $builder->addWarnings($metadata, []);

        expect($result)->not->toHaveKey('warnings')
            ->and($result)->not->toHaveKey('warnings_count');
    });
});

describe('add deduplication stats', function () {
    test('adds deduplication stats to metadata', function () {
        $builder = new SamResponseBuilder;

        $metadata = ['cache_hits' => 5];
        $dedupStats = [
            'duplicates_removed' => 15,
            'deduplication_rate' => '20%',
            'total_before_dedup' => 75,
        ];

        $result = $builder->addDeduplicationStats($metadata, $dedupStats);

        expect($result['duplicates_removed'])->toBe(15)
            ->and($result['deduplication_rate'])->toBe('20%')
            ->and($result['count_before_dedup'])->toBe(75)
            ->and($result['cache_hits'])->toBe(5); // Original metadata preserved
    });

    test('handles missing dedup stats gracefully', function () {
        $builder = new SamResponseBuilder;

        $result = $builder->addDeduplicationStats([], []);

        expect($result['duplicates_removed'])->toBe(0)
            ->and($result['deduplication_rate'])->toBe('0%')
            ->and($result['count_before_dedup'])->toBe(0);
    });
});

describe('response checkers', function () {
    test('isSuccess returns true for success response', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->success([]);

        expect($builder->isSuccess($response))->toBeTrue()
            ->and($builder->isPartialSuccess($response))->toBeFalse()
            ->and($builder->isFailure($response))->toBeFalse();
    });

    test('isPartialSuccess returns true for partial success response', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->partialSuccess([], [], []);

        expect($builder->isPartialSuccess($response))->toBeTrue()
            ->and($builder->isSuccess($response))->toBeFalse()
            ->and($builder->isFailure($response))->toBeFalse();
    });

    test('isFailure returns true for failure response', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->failure('Failed');

        expect($builder->isFailure($response))->toBeTrue()
            ->and($builder->isSuccess($response))->toBeFalse()
            ->and($builder->isPartialSuccess($response))->toBeFalse();
    });
});

describe('response getters', function () {
    test('getOpportunities returns opportunities array', function () {
        $builder = new SamResponseBuilder;

        $opportunities = [['notice_id' => '1'], ['notice_id' => '2']];
        $response = $builder->success($opportunities);

        $result = $builder->getOpportunities($response);

        expect($result)->toBe($opportunities);
    });

    test('getOpportunities returns empty array for failure', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->failure('Failed');

        $result = $builder->getOpportunities($response);

        expect($result)->toBeEmpty();
    });

    test('getMetadata returns metadata array', function () {
        $builder = new SamResponseBuilder;

        $metadata = ['naics_queried' => 5, 'cache_hits' => 3];
        $response = $builder->success([], $metadata);

        $result = $builder->getMetadata($response);

        expect($result['naics_queried'])->toBe(5)
            ->and($result['cache_hits'])->toBe(3);
    });

    test('getErrors returns errors array', function () {
        $builder = new SamResponseBuilder;

        $errors = [['message' => 'Test error']];
        $response = $builder->partialSuccess([], [], $errors);

        $result = $builder->getErrors($response);

        expect($result)->toHaveCount(1)
            ->and($result[0]['message'])->toBe('Test error');
    });

    test('getErrors returns empty array for success', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->success([]);

        $result = $builder->getErrors($response);

        expect($result)->toBeEmpty();
    });
});

describe('error formatting', function () {
    test('formats errors with all fields', function () {
        $builder = new SamResponseBuilder;

        $errors = [
            [
                'message' => 'Test error',
                'naics' => '123456',
                'type' => 'timeout',
                'details' => 'Connection timed out after 30s',
            ],
        ];

        $response = $builder->partialSuccess([], [], $errors);

        expect($response['errors'][0])->toHaveKeys(['message', 'naics', 'type', 'details'])
            ->and($response['errors'][0]['message'])->toBe('Test error')
            ->and($response['errors'][0]['naics'])->toBe('123456')
            ->and($response['errors'][0]['type'])->toBe('timeout')
            ->and($response['errors'][0]['details'])->toBe('Connection timed out after 30s');
    });

    test('handles errors with error key instead of message', function () {
        $builder = new SamResponseBuilder;

        $errors = [['error' => 'Using error key']];

        $response = $builder->partialSuccess([], [], $errors);

        expect($response['errors'][0]['message'])->toBe('Using error key');
    });

    test('handles malformed errors gracefully', function () {
        $builder = new SamResponseBuilder;

        $errors = [[]];

        $response = $builder->partialSuccess([], [], $errors);

        expect($response['errors'][0]['message'])->toBe('Unknown error');
    });
});

describe('metadata formatting', function () {
    test('includes all standard metadata fields', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->success([]);

        expect($response['metadata'])->toHaveKeys([
            'total_count',
            'naics_queried',
            'naics_succeeded',
            'naics_failed',
            'cache_hits',
            'cache_misses',
            'total_duration_ms',
        ]);
    });

    test('uses default values for missing metadata', function () {
        $builder = new SamResponseBuilder;

        $response = $builder->success([]);

        expect($response['metadata']['naics_queried'])->toBe(0)
            ->and($response['metadata']['cache_hits'])->toBe(0)
            ->and($response['metadata']['total_duration_ms'])->toBe(0);
    });
});

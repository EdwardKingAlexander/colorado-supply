<?php

declare(strict_types=1);

use App\Services\SamApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Prevent actual HTTP requests
    Http::preventStrayRequests();
});

describe('successful API requests', function () {
    test('fetches opportunities successfully', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 2,
                'opportunitiesData' => [
                    [
                        'noticeId' => 'ABC123',
                        'solicitationNumber' => 'SOL-2025-001',
                        'title' => 'Test Opportunity 1',
                        'type' => 'Solicitation',
                        'postedDate' => '2025-01-15T10:00:00Z',
                        'responseDeadLine' => '2025-02-15T17:00:00Z',
                        'naics' => '123456',
                        'psc' => '5340',
                        'placeOfPerformance' => [
                            'state' => ['code' => 'CO'],
                        ],
                        'department' => ['name' => 'Department of Defense'],
                        'typeOfSetAsideDescription' => 'Small Business',
                        'url' => 'https://sam.gov/opportunities/ABC123',
                    ],
                    [
                        'noticeId' => 'DEF456',
                        'solicitationNumber' => 'SOL-2025-002',
                        'title' => 'Test Opportunity 2',
                        'type' => 'Presolicitation',
                        'postedDate' => '2025-01-20T10:00:00Z',
                        'naics' => '123456',
                    ],
                ],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o', 'p'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-api-key');

        expect($result)
            ->toHaveKey('success')
            ->toHaveKey('naics')
            ->toHaveKey('count')
            ->toHaveKey('total_records')
            ->toHaveKey('opportunities')
            ->and($result['success'])->toBeTrue()
            ->and($result['naics'])->toBe('123456')
            ->and($result['count'])->toBe(2)
            ->and($result['total_records'])->toBe(2)
            ->and($result['cached'])->toBeFalse();

        expect($result['opportunities'])
            ->toHaveCount(2)
            ->and($result['opportunities'][0])
            ->toHaveKey('notice_id')
            ->toHaveKey('solicitation_number')
            ->toHaveKey('title')
            ->toHaveKey('notice_type')
            ->toHaveKey('posted_date')
            ->toHaveKey('response_deadline')
            ->toHaveKey('naics_code')
            ->toHaveKey('psc_code')
            ->toHaveKey('state_code')
            ->toHaveKey('agency_name')
            ->toHaveKey('set_aside_type')
            ->toHaveKey('sam_url')
            ->and($result['opportunities'][0]['notice_id'])->toBe('ABC123')
            ->and($result['opportunities'][0]['title'])->toBe('Test Opportunity 1')
            ->and($result['opportunities'][0]['posted_date'])->toBe('2025-01-15')
            ->and($result['opportunities'][0]['response_deadline'])->toBe('2025-02-15')
            ->and($result['opportunities'][0]['state_code'])->toBe('CO')
            ->and($result['opportunities'][0]['agency_name'])->toBe('Department of Defense');
    });

    test('sends correct query parameters to API', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 0,
                'opportunitiesData' => [],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o', 'p', 'k'],
            'place' => 'TX',
        ];

        $client->fetch('234567', $params, 'my-api-key');

        Http::assertSent(function ($request) {
            $url = $request->url();

            // Check base URL (without query params)
            return str_contains($url, 'api.sam.gov/opportunities/v2/search')
                && $request['api_key'] === 'my-api-key'
                && $request['postedFrom'] === '01/01/2025'
                && $request['postedTo'] === '01/31/2025'
                && $request['ncode'] === '234567'
                && $request['ptype'] === 'o,p,k'
                && $request['state'] === 'TX'
                && $request['limit'] === 500;
        });
    });

    test('omits state parameter when place is null', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 0,
                'opportunitiesData' => [],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => null,
        ];

        $client->fetch('123456', $params, 'test-key');

        Http::assertSent(function ($request) {
            return ! isset($request['state']);
        });
    });

    test('handles empty opportunities array', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 0,
                'opportunitiesData' => [],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['success'])->toBeTrue()
            ->and($result['count'])->toBe(0)
            ->and($result['opportunities'])->toBe([]);
    });
});

describe('rate limiting', function () {
    test('retries on 429 rate limit with exponential backoff', function () {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            if ($attempts <= 2) {
                return Http::response('Rate limit exceeded', 429);
            }

            return Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [
                    ['noticeId' => 'SUCCESS', 'title' => 'Success'],
                ],
            ], 200);
        });

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($attempts)->toBe(3)
            ->and($result['success'])->toBeTrue()
            ->and($result['opportunities'][0]['notice_id'])->toBe('SUCCESS');
    });

    test('returns error after max retries on persistent 429', function () {
        Log::shouldReceive('warning')->times(3); // 3 retry warnings
        Log::shouldReceive('error')->once(); // Final exhaustion error

        Http::fake([
            'api.sam.gov/*' => Http::response('Rate limit exceeded', 429),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result)
            ->toHaveKey('success')
            ->toHaveKey('naics')
            ->toHaveKey('error')
            ->toHaveKey('status_code')
            ->and($result['success'])->toBeFalse()
            ->and($result['naics'])->toBe('123456')
            ->and($result['error'])->toContain('Rate limit exceeded')
            ->and($result['status_code'])->toBe(429);
    });
});

describe('HTTP error handling', function () {
    test('handles 401 authentication error', function () {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov API returned error status'
                    && $context['error_category'] === 'authentication'
                    && $context['status_code'] === 401;
            });

        Http::fake([
            'api.sam.gov/*' => Http::response('Unauthorized', 401),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'bad-key');

        expect($result['success'])->toBeFalse()
            ->and($result['status_code'])->toBe(401)
            ->and($result['error'])->toBe('SAM.gov API request failed');
    });

    test('handles 404 endpoint not found', function () {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $context['error_category'] === 'endpoint_not_found'
                    && $context['status_code'] === 404;
            });

        Http::fake([
            'api.sam.gov/*' => Http::response('Not Found', 404),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['success'])->toBeFalse()
            ->and($result['status_code'])->toBe(404);
    });

    test('handles 500 server error', function () {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $context['error_category'] === 'server_error'
                    && $context['status_code'] === 500;
            });

        Http::fake([
            'api.sam.gov/*' => Http::response('Internal Server Error', 500),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['success'])->toBeFalse()
            ->and($result['status_code'])->toBe(500);
    });

    test('handles 503 service unavailable', function () {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $context['error_category'] === 'server_error'
                    && $context['status_code'] === 503;
            });

        Http::fake([
            'api.sam.gov/*' => Http::response('Service Unavailable', 503),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['success'])->toBeFalse()
            ->and($result['status_code'])->toBe(503);
    });
});

describe('network error handling', function () {
    test('handles connection timeout', function () {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov network error'
                    && $context['error_category'] === 'network_error';
            });

        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toContain('Network error')
            ->and($result['status_code'])->toBeNull();
    });
});

describe('data validation', function () {
    test('handles invalid JSON response', function () {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov API returned unexpected response structure'
                    && $context['error_category'] === 'data_error';
            });

        Http::fake([
            'api.sam.gov/*' => Http::response('not-json-string', 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toBe('Unexpected response structure from API');
    });

    test('handles missing opportunitiesData field', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 10,
                // Missing opportunitiesData
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['success'])->toBeTrue()
            ->and($result['opportunities'])->toBe([])
            ->and($result['count'])->toBe(0);
    });
});

describe('opportunity mapping', function () {
    test('maps all 13 schema fields correctly', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [
                    [
                        'noticeId' => 'NOTICE-123',
                        'solicitationNumber' => 'SOL-456',
                        'title' => 'Supply Hardware',
                        'type' => 'Combined Synopsis/Solicitation',
                        'postedDate' => '2025-03-15T09:30:00Z',
                        'responseDeadLine' => '2025-04-15T17:00:00Z',
                        'naics' => '423840',
                        'psc' => '5340',
                        'placeOfPerformance' => [
                            'state' => ['code' => 'CA'],
                        ],
                        'department' => ['name' => 'Department of Energy'],
                        'typeOfSetAsideDescription' => '8(a) Set-Aside',
                        'url' => 'https://sam.gov/opp/notice-123',
                        'lastModifiedDate' => '2025-03-16T14:20:00Z',
                    ],
                ],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '03/01/2025',
            'posted_to' => '03/31/2025',
            'notice_type_codes' => ['k'],
            'place' => 'CA',
        ];

        $result = $client->fetch('423840', $params, 'test-key');

        $opp = $result['opportunities'][0];

        expect($opp['notice_id'])->toBe('NOTICE-123')
            ->and($opp['solicitation_number'])->toBe('SOL-456')
            ->and($opp['title'])->toBe('Supply Hardware')
            ->and($opp['notice_type'])->toBe('Combined Synopsis/Solicitation')
            ->and($opp['posted_date'])->toBe('2025-03-15')
            ->and($opp['response_deadline'])->toBe('2025-04-15')
            ->and($opp['naics_code'])->toBe('423840')
            ->and($opp['psc_code'])->toBe('5340')
            ->and($opp['state_code'])->toBe('CA')
            ->and($opp['agency_name'])->toBe('Department of Energy')
            ->and($opp['set_aside_type'])->toBe('8(a) Set-Aside')
            ->and($opp['sam_url'])->toBe('https://sam.gov/opp/notice-123')
            ->and($opp['lastModifiedDate'])->toBe('2025-03-16T14:20:00Z');
    });

    test('uses default values for missing fields', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [
                    [
                        // Minimal data
                        'noticeId' => 'MIN-001',
                    ],
                ],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        $opp = $result['opportunities'][0];

        expect($opp['notice_id'])->toBe('MIN-001')
            ->and($opp['solicitation_number'])->toBeNull()
            ->and($opp['title'])->toBe('Untitled')
            ->and($opp['notice_type'])->toBe('Unknown')
            ->and($opp['posted_date'])->toBeNull()
            ->and($opp['response_deadline'])->toBeNull()
            ->and($opp['naics_code'])->toBeNull()
            ->and($opp['psc_code'])->toBeNull()
            ->and($opp['state_code'])->toBeNull()
            ->and($opp['agency_name'])->toBeNull()
            ->and($opp['set_aside_type'])->toBeNull()
            ->and($opp['sam_url'])->toBeNull();
    });

    test('extracts state code from place string', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [
                    [
                        'noticeId' => 'TEST-001',
                        'place' => 'Denver, CO',
                    ],
                ],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['opportunities'][0]['state_code'])->toBe('CO');
    });

    test('tries multiple agency name fields', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [
                    [
                        'noticeId' => 'TEST-001',
                        'fullParentPathName' => 'Defense.Army.Corps',
                    ],
                ],
            ], 200),
        ]);

        $client = new SamApiClient;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $client->fetch('123456', $params, 'test-key');

        expect($result['opportunities'][0]['agency_name'])->toBe('Defense.Army.Corps');
    });
});

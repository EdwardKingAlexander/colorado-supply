<?php

declare(strict_types=1);

use App\Models\GsaFilter;
use App\Support\GsaFilterSet;
use App\Support\SamParameterResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear GsaFilter table before each test
    GsaFilter::query()->delete();

    // Clear cache
    Cache::flush();
});

describe('NAICS code resolution', function () {
    test('uses override NAICS codes when provided', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'naics_override' => ['123456', '234567'],
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['naics_codes'])
            ->toBe(['123456', '234567']);
    });

    test('loads NAICS codes from database when no override provided', function () {
        GsaFilter::create([
            'type' => 'naics',
            'code' => '111111',
            'description' => 'Test NAICS 1',
            'enabled' => true,
        ]);

        GsaFilter::create([
            'type' => 'naics',
            'code' => '222222',
            'description' => 'Test NAICS 2',
            'enabled' => true,
        ]);

        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['naics_codes'])
            ->toContain('111111', '222222')
            ->toHaveCount(2);
    });

    test('only loads enabled NAICS codes from database', function () {
        GsaFilter::create([
            'type' => 'naics',
            'code' => '111111',
            'description' => 'Enabled NAICS',
            'enabled' => true,
        ]);

        GsaFilter::create([
            'type' => 'naics',
            'code' => '222222',
            'description' => 'Disabled NAICS',
            'enabled' => false,
        ]);

        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['naics_codes'])
            ->toContain('111111')
            ->not->toContain('222222')
            ->toHaveCount(1);
    });

    test('falls back to GsaFilterSet defaults when no DB records or override', function () {
        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        $expectedDefaults = GsaFilterSet::getDefaultNaicsCodes();

        expect($resolved['naics_codes'])
            ->toBe($expectedDefaults);
    });

    test('throws exception when naics_override is not an array', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'naics_override' => '123456', // String instead of array
        ];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'naics_override must be an array');

    test('throws exception when NAICS code is not a string', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'naics_override' => [123456], // Integer instead of string
        ];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'All NAICS codes must be strings');

    test('throws exception when NAICS code format is invalid', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'naics_override' => ['12345'], // Only 5 digits
        ];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'Invalid NAICS code format: 12345. Expected 6 digits.');

    test('throws exception when no NAICS codes are available anywhere', function () {
        // Mock GsaFilterSet to return empty array
        Cache::forget('gsa_default_naics');
        Cache::put('gsa_default_naics', [], 3600);

        $resolver = new SamParameterResolver;

        $resolver->resolve([]);
    })->throws(InvalidArgumentException::class, 'No NAICS codes available');
});

describe('PSC code resolution', function () {
    test('uses override PSC codes when provided', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'psc_override' => ['5340', '5305'],
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['psc_codes'])
            ->toBe(['5340', '5305']);
    });

    test('loads PSC codes from database when no override provided', function () {
        GsaFilter::create([
            'type' => 'psc',
            'code' => '5340',
            'description' => 'Hardware',
            'enabled' => true,
        ]);

        GsaFilter::create([
            'type' => 'psc',
            'code' => '5305',
            'description' => 'Screws',
            'enabled' => true,
        ]);

        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['psc_codes'])
            ->toContain('5340', '5305');
    });

    test('only loads enabled PSC codes from database', function () {
        GsaFilter::create([
            'type' => 'psc',
            'code' => '5340',
            'description' => 'Enabled PSC',
            'enabled' => true,
        ]);

        GsaFilter::create([
            'type' => 'psc',
            'code' => '5305',
            'description' => 'Disabled PSC',
            'enabled' => false,
        ]);

        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['psc_codes'])
            ->toContain('5340')
            ->not->toContain('5305');
    });

    test('falls back to GsaFilterSet defaults when no DB records or override', function () {
        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        $expectedDefaults = GsaFilterSet::getDefaultPscCodes();

        expect($resolved['psc_codes'])
            ->toBe($expectedDefaults);
    });

    test('throws exception when psc_override is not an array', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'psc_override' => '5340', // String instead of array
        ];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'psc_override must be an array');
});

describe('notice type resolution', function () {
    test('uses default notice types when not provided', function () {
        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['notice_types'])
            ->toBe([
                'Presolicitation',
                'Solicitation',
                'Combined Synopsis/Solicitation',
            ]);
    });

    test('uses override notice types when provided', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'notice_type' => ['Solicitation', 'Sources Sought'],
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['notice_types'])
            ->toBe(['Solicitation', 'Sources Sought']);
    });

    test('converts notice types to v2 single-letter codes', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'notice_type' => [
                'Presolicitation',
                'Solicitation',
                'Combined Synopsis/Solicitation',
                'Sources Sought',
            ],
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['notice_type_codes'])
            ->toBe(['p', 'o', 'k', 's']);
    });

    test('uses default codes when no notice types match', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'notice_type' => ['UnknownType'],
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['notice_type_codes'])
            ->toBe(['o', 'p', 'k']);
    });

    test('throws exception when notice_type is not an array', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'notice_type' => 'Solicitation', // String instead of array
        ];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'notice_type must be an array');
});

describe('place of performance resolution', function () {
    test('defaults to config value when place not provided', function () {
        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        // When place key is not in params at all, uses config default ('CO')
        expect($resolved['place'])->toBe('CO')
            ->and($resolved['query_metadata']['state_code'])->toBe('CO');
    });

    test('uses nationwide when place is explicitly null', function () {
        $resolver = new SamParameterResolver;

        // Explicitly setting place to null (as control panel does for nationwide)
        $params = ['place' => null];

        $resolved = $resolver->resolve($params);

        expect($resolved['place'])->toBeNull()
            ->and($resolved['query_metadata']['state_code'])->toBe('nationwide');
    });

    test('uses override state code when provided', function () {
        $resolver = new SamParameterResolver;

        $params = ['place' => 'CA'];

        $resolved = $resolver->resolve($params);

        expect($resolved['place'])->toBe('CA');
    });

    test('normalizes state code to uppercase', function () {
        $resolver = new SamParameterResolver;

        $params = ['place' => 'tx'];

        $resolved = $resolver->resolve($params);

        expect($resolved['place'])->toBe('TX');
    });

    test('returns null when place is empty string', function () {
        $resolver = new SamParameterResolver;

        $params = ['place' => ''];

        $resolved = $resolver->resolve($params);

        expect($resolved['place'])->toBeNull();
    });

    test('throws exception when state code format is invalid', function () {
        $resolver = new SamParameterResolver;

        $params = ['place' => 'ABC']; // 3 letters

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'Invalid state code: ABC');
});

describe('date range resolution', function () {
    test('builds date range from days_back parameter', function () {
        Carbon::setTestNow('2025-11-19 12:00:00');

        $resolver = new SamParameterResolver;

        $params = ['days_back' => 30];

        $resolved = $resolver->resolve($params);

        expect($resolved['posted_from'])->toBe('10/20/2025')
            ->and($resolved['posted_to'])->toBe('11/19/2025')
            ->and($resolved['days_back'])->toBe(30);
    });

    test('uses custom posted_from and posted_to when provided', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'posted_from' => '2025-01-01',
            'posted_to' => '2025-01-31',
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['posted_from'])->toBe('01/01/2025')
            ->and($resolved['posted_to'])->toBe('01/31/2025');
    });

    test('formats ISO 8601 dates to SAM.gov v2 format', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'posted_from' => '2025-03-15',
            'posted_to' => '2025-04-20',
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['posted_from'])->toBe('03/15/2025')
            ->and($resolved['posted_to'])->toBe('04/20/2025');
    });

    test('throws exception when date format is invalid', function () {
        $resolver = new SamParameterResolver;

        $params = [
            'posted_from' => 'invalid-date',
            'posted_to' => '2025-01-31',
        ];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'Invalid date format: invalid-date');

    test('throws exception when days_back is less than 1', function () {
        $resolver = new SamParameterResolver;

        $params = ['days_back' => 0];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'Invalid days_back: 0. Must be between 1 and 365.');

    test('throws exception when days_back is greater than 365', function () {
        $resolver = new SamParameterResolver;

        $params = ['days_back' => 400];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'Invalid days_back: 400. Must be between 1 and 365.');
});

describe('limit resolution', function () {
    test('uses default limit of 50 when not provided', function () {
        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['limit'])->toBe(50);
    });

    test('uses override limit when provided', function () {
        $resolver = new SamParameterResolver;

        $params = ['limit' => 100];

        $resolved = $resolver->resolve($params);

        expect($resolved['limit'])->toBe(100);
    });

    test('throws exception when limit is less than 1', function () {
        $resolver = new SamParameterResolver;

        $params = ['limit' => 0];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'Invalid limit: 0. Must be between 1 and 1000.');

    test('throws exception when limit is greater than 1000', function () {
        $resolver = new SamParameterResolver;

        $params = ['limit' => 1500];

        $resolver->resolve($params);
    })->throws(InvalidArgumentException::class, 'Invalid limit: 1500. Must be between 1 and 1000.');
});

describe('other parameters resolution', function () {
    test('uses default keywords null when not provided', function () {
        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['keywords'])->toBeNull();
    });

    test('uses override keywords when provided', function () {
        $resolver = new SamParameterResolver;

        $params = ['keywords' => 'fasteners'];

        $resolved = $resolver->resolve($params);

        expect($resolved['keywords'])->toBe('fasteners');
    });

    test('uses default clearCache false when not provided', function () {
        $resolver = new SamParameterResolver;
        $resolved = $resolver->resolve([]);

        expect($resolved['clearCache'])->toBeFalse();
    });

    test('uses override clearCache when provided', function () {
        $resolver = new SamParameterResolver;

        $params = ['clearCache' => true];

        $resolved = $resolver->resolve($params);

        expect($resolved['clearCache'])->toBeTrue();
    });
});

describe('query metadata generation', function () {
    test('builds complete query metadata object', function () {
        Carbon::setTestNow('2025-11-19 12:00:00');

        GsaFilter::create([
            'type' => 'naics',
            'code' => '123456',
            'enabled' => true,
        ]);

        $resolver = new SamParameterResolver;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'keywords' => 'hardware',
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved['query_metadata'])
            ->toHaveKey('date_range')
            ->toHaveKey('naics_codes')
            ->toHaveKey('psc_codes')
            ->toHaveKey('state_code')
            ->toHaveKey('notice_types')
            ->toHaveKey('keywords')
            ->and($resolved['query_metadata']['date_range'])->toBe('10/20/2025 to 11/19/2025')
            ->and($resolved['query_metadata']['state_code'])->toBe('CO')
            ->and($resolved['query_metadata']['keywords'])->toBe('hardware');
    });

    test('shows nationwide when place is empty string', function () {
        $resolver = new SamParameterResolver;

        $params = ['place' => ''];

        $resolved = $resolver->resolve($params);

        expect($resolved['query_metadata']['state_code'])->toBe('nationwide');
    });

    test('shows nationwide when place is explicitly null', function () {
        $resolver = new SamParameterResolver;

        $params = ['place' => null];

        $resolved = $resolver->resolve($params);

        expect($resolved['query_metadata']['state_code'])->toBe('nationwide');
    });
});

describe('complete resolution integration', function () {
    test('resolves all parameters correctly with mixed overrides and defaults', function () {
        Carbon::setTestNow('2025-11-19 12:00:00');

        // Set up some DB defaults
        GsaFilter::create([
            'type' => 'naics',
            'code' => '111111',
            'enabled' => true,
        ]);

        GsaFilter::create([
            'type' => 'psc',
            'code' => '5340',
            'enabled' => true,
        ]);

        $resolver = new SamParameterResolver;

        $params = [
            'naics_override' => ['999999'], // Override NAICS
            // PSC will come from DB
            'place' => 'tx',
            'days_back' => 60,
            'limit' => 75,
            'keywords' => 'bolts',
            'clearCache' => true,
            'notice_type' => ['Solicitation'],
        ];

        $resolved = $resolver->resolve($params);

        expect($resolved)
            ->toHaveKey('naics_codes')
            ->toHaveKey('psc_codes')
            ->toHaveKey('notice_types')
            ->toHaveKey('notice_type_codes')
            ->toHaveKey('place')
            ->toHaveKey('days_back')
            ->toHaveKey('limit')
            ->toHaveKey('keywords')
            ->toHaveKey('clearCache')
            ->toHaveKey('posted_from')
            ->toHaveKey('posted_to')
            ->toHaveKey('query_metadata')
            ->and($resolved['naics_codes'])->toBe(['999999'])
            ->and($resolved['psc_codes'])->toContain('5340')
            ->and($resolved['place'])->toBe('TX')
            ->and($resolved['days_back'])->toBe(60)
            ->and($resolved['limit'])->toBe(75)
            ->and($resolved['keywords'])->toBe('bolts')
            ->and($resolved['clearCache'])->toBeTrue()
            ->and($resolved['notice_type_codes'])->toBe(['o']);
    });
});

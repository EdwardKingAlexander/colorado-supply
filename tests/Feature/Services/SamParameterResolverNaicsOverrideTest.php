<?php

declare(strict_types=1);

use App\Support\SamParameterResolver;

test('naics_override parameter works correctly', function () {
    $resolver = new SamParameterResolver;

    $params = [
        'naics_override' => ['236220', '541330', '562910'],
    ];

    $resolved = $resolver->resolve($params);

    // Verify that ONLY the override NAICS codes are returned
    expect($resolved['naics_codes'])
        ->toHaveCount(3)
        ->toContain('236220')
        ->toContain('541330')
        ->toContain('562910');

    // Verify the default NAICS codes are NOT included
    expect($resolved['naics_codes'])->not->toContain('423840'); // First default NAICS
});

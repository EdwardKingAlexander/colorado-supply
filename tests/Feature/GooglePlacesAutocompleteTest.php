<?php

use App\Services\Google\PlacesAutocompleteService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

test('service throws exception when API key is not configured', function () {
    Config::set('services.google.places.api_key', '');

    expect(fn() => new PlacesAutocompleteService())
        ->toThrow(RuntimeException::class, 'Google Places API key is not configured.');
});

test('service accepts custom API key in constructor', function () {
    Config::set('services.google.places.api_key', '');

    $service = new PlacesAutocompleteService('custom-api-key');

    expect($service)->toBeInstanceOf(PlacesAutocompleteService::class);
});

test('fetchPredictions returns predictions for valid input', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => '1600 Amphitheatre Parkway, Mountain View, CA',
                    'place_id' => 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA',
                ],
                [
                    'description' => '1 Infinite Loop, Cupertino, CA',
                    'place_id' => 'ChIJHTRqF7e1j4AR0rKZvEU6iLQ',
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $predictions = $service->fetchPredictions('1600 Amphitheatre');

    expect($predictions)->toHaveCount(2)
        ->and($predictions[0]['description'])->toBe('1600 Amphitheatre Parkway, Mountain View, CA')
        ->and($predictions[0]['place_id'])->toBe('ChIJ2eUgeAK6j4ARbn5u_wAGqWA');
});

test('fetchPredictions returns empty array for ZERO_RESULTS', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'ZERO_RESULTS',
            'predictions' => [],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $predictions = $service->fetchPredictions('invalid address xyz123');

    expect($predictions)->toBeEmpty();
});

test('fetchPredictions returns empty array for empty input', function () {
    $service = app(PlacesAutocompleteService::class);

    expect($service->fetchPredictions(''))->toBeEmpty()
        ->and($service->fetchPredictions('   '))->toBeEmpty();
});

test('fetchPredictions throws exception on API error', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'REQUEST_DENIED',
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect(fn() => $service->fetchPredictions('test'))
        ->toThrow(RuntimeException::class, 'Google Places autocomplete error: REQUEST_DENIED');
});

test('fetchPredictions throws exception on non-JSON response', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response('Not JSON', 200),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect(fn() => $service->fetchPredictions('test'))
        ->toThrow(RuntimeException::class, 'Google Places autocomplete response was not JSON.');
});

test('fetchPredictions throws exception on failed HTTP request', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([], 500),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect(fn() => $service->fetchPredictions('test'))
        ->toThrow(RuntimeException::class, 'Google Places autocomplete request failed with status 500.');
});

test('fetchTopPrediction returns first prediction', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => '1600 Amphitheatre Parkway, Mountain View, CA',
                    'place_id' => 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA',
                ],
                [
                    'description' => '1 Infinite Loop, Cupertino, CA',
                    'place_id' => 'ChIJHTRqF7e1j4AR0rKZvEU6iLQ',
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $prediction = $service->fetchTopPrediction('1600 Amphitheatre');

    expect($prediction)->not->toBeNull()
        ->and($prediction['description'])->toBe('1600 Amphitheatre Parkway, Mountain View, CA')
        ->and($prediction['place_id'])->toBe('ChIJ2eUgeAK6j4ARbn5u_wAGqWA');
});

test('fetchTopPrediction returns null when no predictions', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'ZERO_RESULTS',
            'predictions' => [],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect($service->fetchTopPrediction('invalid'))->toBeNull();
});

test('fetchPlaceDetails returns place details', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA',
                'formatted_address' => '1600 Amphitheatre Pkwy, Mountain View, CA 94043, USA',
                'address_components' => [
                    ['long_name' => '1600', 'short_name' => '1600', 'types' => ['street_number']],
                    ['long_name' => 'Amphitheatre Parkway', 'short_name' => 'Amphitheatre Pkwy', 'types' => ['route']],
                    ['long_name' => 'Mountain View', 'short_name' => 'Mountain View', 'types' => ['locality']],
                    ['long_name' => 'California', 'short_name' => 'CA', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '94043', 'short_name' => '94043', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 37.4224764,
                        'lng' => -122.0842499,
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $details = $service->fetchPlaceDetails('ChIJ2eUgeAK6j4ARbn5u_wAGqWA');

    expect($details)->not->toBeNull()
        ->and($details['place_id'])->toBe('ChIJ2eUgeAK6j4ARbn5u_wAGqWA')
        ->and($details['formatted_address'])->toBe('1600 Amphitheatre Pkwy, Mountain View, CA 94043, USA')
        ->and($details['address_components'])->toHaveCount(6);
});

test('fetchPlaceDetails returns null for empty place_id', function () {
    $service = app(PlacesAutocompleteService::class);

    expect($service->fetchPlaceDetails(''))->toBeNull()
        ->and($service->fetchPlaceDetails('   '))->toBeNull();
});

test('fetchPlaceDetails returns null for ZERO_RESULTS', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'ZERO_RESULTS',
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect($service->fetchPlaceDetails('invalid-place-id'))->toBeNull();
});

test('fetchPlaceDetails throws exception on API error', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'INVALID_REQUEST',
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect(fn() => $service->fetchPlaceDetails('test'))
        ->toThrow(RuntimeException::class, 'Google Places details error: INVALID_REQUEST');
});

test('resolveAddress returns complete address data', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => '1600 Amphitheatre Parkway, Mountain View, CA',
                    'place_id' => 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA',
                'formatted_address' => '1600 Amphitheatre Pkwy, Mountain View, CA 94043, USA',
                'address_components' => [
                    ['long_name' => '1600', 'short_name' => '1600', 'types' => ['street_number']],
                    ['long_name' => 'Amphitheatre Parkway', 'short_name' => 'Amphitheatre Pkwy', 'types' => ['route']],
                    ['long_name' => 'Mountain View', 'short_name' => 'Mountain View', 'types' => ['locality']],
                    ['long_name' => 'California', 'short_name' => 'CA', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '94043', 'short_name' => '94043', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 37.4224764,
                        'lng' => -122.0842499,
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $address = $service->resolveAddress('1600 Amphitheatre');

    expect($address)->not->toBeNull()
        ->and($address['street'])->toBe('1600 Amphitheatre Parkway')
        ->and($address['line2'])->toBeNull()
        ->and($address['city'])->toBe('Mountain View')
        ->and($address['state'])->toBe('CA')
        ->and($address['zip'])->toBe('94043')
        ->and($address['country'])->toBe('US')
        ->and($address['formatted'])->toBe('1600 Amphitheatre Pkwy, Mountain View, CA 94043, USA')
        ->and($address['place_id'])->toBe('ChIJ2eUgeAK6j4ARbn5u_wAGqWA')
        ->and($address['latitude'])->toBe(37.4224764)
        ->and($address['longitude'])->toBe(-122.0842499);
});

test('resolveAddress handles addresses with subpremise', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => '123 Main St Suite 100, Denver, CO',
                    'place_id' => 'test-place-id',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'test-place-id',
                'formatted_address' => '123 Main St Suite 100, Denver, CO 80202, USA',
                'address_components' => [
                    ['long_name' => '100', 'short_name' => '100', 'types' => ['subpremise']],
                    ['long_name' => '123', 'short_name' => '123', 'types' => ['street_number']],
                    ['long_name' => 'Main Street', 'short_name' => 'Main St', 'types' => ['route']],
                    ['long_name' => 'Denver', 'short_name' => 'Denver', 'types' => ['locality']],
                    ['long_name' => 'Colorado', 'short_name' => 'CO', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '80202', 'short_name' => '80202', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 39.7392,
                        'lng' => -104.9903,
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $address = $service->resolveAddress('123 Main St Suite 100');

    expect($address)->not->toBeNull()
        ->and($address['street'])->toBe('123 Main Street')
        ->and($address['line2'])->toBe('100')
        ->and($address['city'])->toBe('Denver')
        ->and($address['state'])->toBe('CO')
        ->and($address['zip'])->toBe('80202')
        ->and($address['country'])->toBe('US');
});

test('resolveAddress returns null for empty input', function () {
    $service = app(PlacesAutocompleteService::class);

    expect($service->resolveAddress(''))->toBeNull()
        ->and($service->resolveAddress('   '))->toBeNull();
});

test('resolveAddress returns null when no predictions found', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'ZERO_RESULTS',
            'predictions' => [],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect($service->resolveAddress('invalid address xyz123'))->toBeNull();
});

test('resolveAddress returns null when place details not found', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => 'Test Address',
                    'place_id' => 'test-place-id',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'ZERO_RESULTS',
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);

    expect($service->resolveAddress('Test Address'))->toBeNull();
});

test('resolveAddress handles addresses without street number', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => 'Main Street, Denver, CO',
                    'place_id' => 'test-place-id',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'test-place-id',
                'formatted_address' => 'Main St, Denver, CO 80202, USA',
                'address_components' => [
                    ['long_name' => 'Main Street', 'short_name' => 'Main St', 'types' => ['route']],
                    ['long_name' => 'Denver', 'short_name' => 'Denver', 'types' => ['locality']],
                    ['long_name' => 'Colorado', 'short_name' => 'CO', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '80202', 'short_name' => '80202', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 39.7392,
                        'lng' => -104.9903,
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $address = $service->resolveAddress('Main Street');

    expect($address)->not->toBeNull()
        ->and($address['street'])->toBe('Main Street')
        ->and($address['city'])->toBe('Denver');
});

test('resolveAddress uses fallback city types when locality not found', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => 'Test Address',
                    'place_id' => 'test-place-id',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'test-place-id',
                'formatted_address' => 'Test Address, UK',
                'address_components' => [
                    ['long_name' => 'Test Street', 'short_name' => 'Test St', 'types' => ['route']],
                    ['long_name' => 'London', 'short_name' => 'London', 'types' => ['postal_town']],
                    ['long_name' => 'England', 'short_name' => 'England', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'United Kingdom', 'short_name' => 'GB', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 51.5074,
                        'lng' => -0.1278,
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $address = $service->resolveAddress('Test Address');

    expect($address)->not->toBeNull()
        ->and($address['city'])->toBe('London');
});

test('service includes session token when provided', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => 'Test Address',
                    'place_id' => 'test-place-id',
                ],
            ],
        ], 200),
    ]);

    $service = app(PlacesAutocompleteService::class);
    $service->fetchPredictions('test', 'test-session-token');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=test&types=address&key=' . config('services.google.places.api_key') . '&sessiontoken=test-session-token';
    });
});

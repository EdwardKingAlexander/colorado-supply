<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlacesAutocompleteService
{
    private string $apiKey;

    private const AUTOCOMPLETE_ENDPOINT = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
    private const DETAILS_ENDPOINT = 'https://maps.googleapis.com/maps/api/place/details/json';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.google.places.api_key');

        if ($this->apiKey === '') {
            throw new RuntimeException('Google Places API key is not configured.');
        }
    }

    public function resolveAddress(string $input, ?string $sessionToken = null): ?array
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        $prediction = $this->fetchTopPrediction($input, $sessionToken);

        if ($prediction === null) {
            return null;
        }

        $details = $this->fetchPlaceDetails((string) ($prediction['place_id'] ?? ''), $sessionToken);

        if ($details === null) {
            return null;
        }

        return $this->mapAddressDetails($details);
    }

    public function fetchPredictions(string $input, ?string $sessionToken = null): array
    {
        $input = trim($input);

        if ($input === '') {
            return [];
        }

        $response = Http::acceptJson()->get(self::AUTOCOMPLETE_ENDPOINT, array_filter([
            'input' => $input,
            'types' => 'address',
            'key' => $this->apiKey,
            'sessiontoken' => $sessionToken,
        ], static fn($value) => $value !== null));

        if (! $response->successful()) {
            throw new RuntimeException('Google Places autocomplete request failed with status ' . $response->status() . '.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Google Places autocomplete response was not JSON.');
        }

        $status = $payload['status'] ?? null;

        if ($status === 'OK' || $status === 'ZERO_RESULTS') {
            return $payload['predictions'] ?? [];
        }

        throw new RuntimeException('Google Places autocomplete error: ' . ($status ?? 'UNKNOWN'));
    }

    public function fetchTopPrediction(string $input, ?string $sessionToken = null): ?array
    {
        $predictions = $this->fetchPredictions($input, $sessionToken);

        return $predictions[0] ?? null;
    }

    public function fetchPlaceDetails(string $placeId, ?string $sessionToken = null): ?array
    {
        $placeId = trim($placeId);

        if ($placeId === '') {
            return null;
        }

        $response = Http::acceptJson()->get(self::DETAILS_ENDPOINT, array_filter([
            'place_id' => $placeId,
            'key' => $this->apiKey,
            'sessiontoken' => $sessionToken,
            'fields' => 'address_component,formatted_address,geometry,place_id',
        ], static fn($value) => $value !== null));

        if (! $response->successful()) {
            throw new RuntimeException('Google Places details request failed with status ' . $response->status() . '.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Google Places details response was not JSON.');
        }

        $status = $payload['status'] ?? null;

        if ($status === 'OK' && isset($payload['result']) && is_array($payload['result'])) {
            return $payload['result'];
        }

        if ($status === 'ZERO_RESULTS') {
            return null;
        }

        throw new RuntimeException('Google Places details error: ' . ($status ?? 'UNKNOWN'));
    }

    private function mapAddressDetails(array $details): array
    {
        $components = $details['address_components'] ?? [];
        $location = $details['geometry']['location'] ?? ['lat' => null, 'lng' => null];

        $streetNumber = $this->extractComponent($components, 'street_number');
        $route = $this->extractComponent($components, 'route');
        $street = trim(($streetNumber ?? '') . ' ' . ($route ?? ''));

        $city = $this->extractComponent($components, 'locality')
            ?? $this->extractComponent($components, 'postal_town')
            ?? $this->extractComponent($components, 'sublocality')
            ?? $this->extractComponent($components, 'administrative_area_level_2');

        return [
            'street' => $street !== '' ? $street : null,
            'line2' => $this->extractComponent($components, 'subpremise'),
            'city' => $city,
            'state' => $this->extractComponent($components, 'administrative_area_level_1', 'short_name'),
            'zip' => $this->extractComponent($components, 'postal_code'),
            'country' => $this->extractComponent($components, 'country', 'short_name'),
            'formatted' => $details['formatted_address'] ?? null,
            'place_id' => $details['place_id'] ?? null,
            'latitude' => $location['lat'] ?? null,
            'longitude' => $location['lng'] ?? null,
        ];
    }

    private function extractComponent(array $components, string $type, string $property = 'long_name'): ?string
    {
        foreach ($components as $component) {
            $types = $component['types'] ?? [];

            if (in_array($type, $types, true)) {
                return $component[$property] ?? null;
            }
        }

        return null;
    }
}

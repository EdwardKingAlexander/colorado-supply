<?php

namespace App\Support\Privacy;

use Illuminate\Http\Request;

/**
 * Parses the first-party consent cookie (cs_privacy_consent). The cookie is
 * excluded from encryption so the browser-side analytics bootstrap can read
 * the same value; treat its contents as untrusted input.
 */
class ConsentCookie
{
    /**
     * @return array{uuid: string, categories: list<string>, version: string}|null
     */
    public static function fromRequest(Request $request): ?array
    {
        return static::parse($request->cookie(config('privacy.consent_cookie.name')));
    }

    /**
     * @return array{uuid: string, categories: list<string>, version: string}|null
     */
    public static function parse(mixed $value): ?array
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return null;
        }

        $uuid = $decoded['uuid'] ?? null;
        $categories = $decoded['categories'] ?? null;
        $version = $decoded['version'] ?? null;

        if (! is_string($uuid) || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return null;
        }

        if (! is_array($categories) || ! is_string($version) || $version === '') {
            return null;
        }

        $known = array_keys(config('privacy.categories', []));
        $categories = array_values(array_unique(array_filter(
            $categories,
            fn ($category): bool => is_string($category) && in_array($category, $known, true),
        )));

        // Essential is always granted regardless of what the cookie claims.
        if (! in_array('essential', $categories, true)) {
            $categories[] = 'essential';
        }

        return [
            'uuid' => strtolower($uuid),
            'categories' => $categories,
            'version' => $version,
        ];
    }
}

<?php

namespace App\Support;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;

class SentryEventScrubber
{
    /**
     * Field names redacted wherever they appear in request data or
     * breadcrumb payloads, regardless of nesting.
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'tax_id',
        'ssn',
        'card_number',
        'cvv',
        'cvc',
        'secret',
        'token',
        'api_key',
        'stripe_signature',
    ];

    private const SENSITIVE_HEADERS = [
        'authorization',
        'cookie',
        'x-xsrf-token',
    ];

    public static function scrub(Event $event, ?EventHint $hint): ?Event
    {
        $request = $event->getRequest();

        if (isset($request['headers']) && is_array($request['headers'])) {
            $request['headers'] = self::redactHeaders($request['headers']);
        }

        if (isset($request['cookies'])) {
            $request['cookies'] = '[Filtered]';
        }

        if (isset($request['data']) && is_array($request['data'])) {
            $request['data'] = self::redactFields($request['data']);
        }

        if ($request !== []) {
            $event->setRequest($request);
        }

        $breadcrumbs = array_map(function (Breadcrumb $breadcrumb) {
            $metadata = $breadcrumb->getMetadata();

            if ($metadata === []) {
                return $breadcrumb;
            }

            return new Breadcrumb(
                $breadcrumb->getLevel(),
                $breadcrumb->getType(),
                $breadcrumb->getCategory(),
                $breadcrumb->getMessage(),
                self::redactFields($metadata),
                $breadcrumb->getTimestamp(),
            );
        }, $event->getBreadcrumbs());

        $event->setBreadcrumb($breadcrumbs);

        return $event;
    }

    private static function redactHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (in_array(strtolower((string) $name), self::SENSITIVE_HEADERS, true)) {
                $headers[$name] = '[Filtered]';
            }
        }

        return $headers;
    }

    private static function redactFields(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::redactFields($value);

                continue;
            }

            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_FIELDS, true)) {
                $data[$key] = '[Filtered]';
            }
        }

        return $data;
    }
}

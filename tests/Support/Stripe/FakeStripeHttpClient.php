<?php

namespace Tests\Support\Stripe;

use Stripe\HttpClient\ClientInterface;

/**
 * A canned-response Stripe HTTP client for tests. Avoids real network calls
 * while exercising the real Stripe SDK request/response cycle.
 */
class FakeStripeHttpClient implements ClientInterface
{
    /** @var array<int, array{method: string, url: string, params: array}> */
    public array $requests = [];

    /**
     * @param  array<string, array{body: array, status?: int}>  $responses  Keyed by "METHOD path", e.g. "post /v1/checkout/sessions".
     */
    public function __construct(private array $responses) {}

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = parse_url($absUrl, PHP_URL_PATH);
        $key = strtolower($method).' '.$path;

        $this->requests[] = ['method' => $method, 'url' => $absUrl, 'params' => $params];

        if (! isset($this->responses[$key])) {
            throw new \RuntimeException("FakeStripeHttpClient has no canned response for [{$key}]");
        }

        $response = $this->responses[$key];

        return [
            json_encode($response['body']),
            $response['status'] ?? 200,
            [],
        ];
    }
}

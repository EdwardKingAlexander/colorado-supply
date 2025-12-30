<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class McpClient
{
    /**
     * Invoke a MCP tool through the JSON-RPC HTTP endpoint.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $endpoint, string $toolName, array $arguments): array
    {
        $response = Http::acceptJson()->post(
            url($endpoint),
            $this->buildPayload($toolName, $arguments),
        );

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'MCP request failed with status %s.',
                $response->status()
            ));
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw new RuntimeException('MCP response body could not be decoded.');
        }

        if (isset($body['error'])) {
            $message = $body['error']['message'] ?? 'Unknown MCP error.';

            throw new RuntimeException($message);
        }

        if (! isset($body['result'])) {
            throw new RuntimeException('MCP response missing result payload.');
        }

        return $body;
    }

    public function extractText(array $payload): ?string
    {
        return data_get($payload, 'result.content.0.text');
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function buildPayload(string $toolName, array $arguments): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => (string) Str::uuid(),
            'method' => 'tools/call',
            'params' => [
                'name' => $toolName,
                'arguments' => $arguments,
            ],
        ];
    }
}

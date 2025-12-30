<?php

use App\Support\McpClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('wraps MCP tool calls in a JSON-RPC envelope', function () {
    Http::fake([
        url('/mcp/test') => Http::response([
            'jsonrpc' => '2.0',
            'id' => '123',
            'result' => [
                'content' => [
                    ['type' => 'text', 'text' => 'ok'],
                ],
                'isError' => false,
            ],
        ]),
    ]);

    $client = new McpClient;

    $response = $client->callTool('/mcp/test', 'sample-tool', ['foo' => 'bar']);

    expect($response)->toHaveKey('result');

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['jsonrpc'] === '2.0'
            && $data['method'] === 'tools/call'
            && $data['params']['name'] === 'sample-tool'
            && $data['params']['arguments'] === ['foo' => 'bar'];
    });
});

it('throws when MCP returns an error payload', function () {
    Http::fake([
        url('/mcp/test') => Http::response([
            'jsonrpc' => '2.0',
            'id' => '123',
            'error' => [
                'code' => -32602,
                'message' => 'Invalid request',
            ],
        ], 200),
    ]);

    $client = new McpClient;

    $client->callTool('/mcp/test', 'sample-tool', ['foo' => 'bar']);
})->throws(\RuntimeException::class, 'Invalid request');

it('extracts assistant text content from responses', function () {
    $client = new McpClient;

    $text = $client->extractText([
        'result' => [
            'content' => [
                ['type' => 'text', 'text' => 'payload'],
            ],
        ],
    ]);

    expect($text)->toBe('payload');
});

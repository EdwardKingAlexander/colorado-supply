<?php

namespace App\Mcp\Servers\ChromeDevTools\Tools;

use App\Mcp\Servers\ChromeDevTools\Services\BrowserService;
use App\Mcp\Servers\Tool;

class NavigateTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'navigate';

    /**
     * The tool's description.
     */
    protected string $description = 'Navigate to a URL and get page information (title, URL, status)';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'The URL to navigate to',
                'required' => true,
            ],
            'wait_until' => [
                'type' => 'string',
                'description' => 'Wait until condition: load, domcontentloaded, networkidle (default: networkidle)',
                'required' => false,
            ],
            'timeout' => [
                'type' => 'integer',
                'description' => 'Timeout in milliseconds (default: 30000)',
                'required' => false,
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $inputs): string
    {
        $url = $inputs['url'];
        $waitUntil = $inputs['wait_until'] ?? 'networkIdle';
        $timeout = $inputs['timeout'] ?? 30000;

        try {
            // Use shared browser service instead of creating new browser
            $page = BrowserService::getPage();

            // Navigate to URL
            $page->navigate($url)->waitForNavigation($waitUntil, $timeout);

            // Get page information
            $title = $page->evaluate('document.title')->getReturnValue();
            $currentUrl = $page->evaluate('window.location.href')->getReturnValue();
            $readyState = $page->evaluate('document.readyState')->getReturnValue();

            return json_encode([
                'success' => true,
                'requested_url' => $url,
                'current_url' => $currentUrl,
                'title' => $title,
                'ready_state' => $readyState,
                'wait_until' => $waitUntil,
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ], JSON_PRETTY_PRINT);
        }
    }
}

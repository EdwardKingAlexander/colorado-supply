<?php

namespace App\Mcp\Servers\ChromeDevTools\Tools;

use App\Mcp\Servers\ChromeDevTools\Services\BrowserService;
use App\Mcp\Servers\Tool;

class GetPageContentTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get-page-content';

    /**
     * The tool's description.
     */
    protected string $description = 'Extract the HTML content of a webpage';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'The URL of the webpage to fetch',
                'required' => true,
            ],
            'selector' => [
                'type' => 'string',
                'description' => 'Optional CSS selector to extract specific content',
                'required' => false,
            ],
            'wait_for_selector' => [
                'type' => 'string',
                'description' => 'Optional CSS selector to wait for before extracting content',
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
        $selector = $inputs['selector'] ?? null;
        $waitForSelector = $inputs['wait_for_selector'] ?? null;
        $timeout = $inputs['timeout'] ?? 30000;

        try {
            // Use shared browser service instead of creating new browser
            $page = BrowserService::getPage();

            // Navigate to URL
            $page->navigate($url)->waitForNavigation('networkIdle', $timeout);

            // Wait for specific selector if provided
            if ($waitForSelector) {
                $this->waitForSelector($page, $waitForSelector, $timeout);
            }

            // Extract content
            if ($selector) {
                // Extract content from specific selector
                $content = $page->evaluate("
                    const element = document.querySelector('".$selector."');
                    element ? element.outerHTML : null;
                ")->getReturnValue();
            } else {
                // Get full page HTML
                $content = $page->evaluate('document.documentElement.outerHTML')->getReturnValue();
            }

            if ($content === null) {
                return json_encode([
                    'success' => false,
                    'error' => 'Selector not found: '.$selector,
                    'url' => $url,
                ], JSON_PRETTY_PRINT);
            }

            return json_encode([
                'success' => true,
                'url' => $url,
                'selector' => $selector,
                'content' => $content,
                'content_length' => strlen($content),
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Wait for a selector to appear on the page using JavaScript polling.
     */
    protected function waitForSelector($page, string $selector, int $timeout): void
    {
        $startTime = microtime(true);
        $timeoutSeconds = $timeout / 1000;

        while (microtime(true) - $startTime < $timeoutSeconds) {
            $exists = $page->evaluate("document.querySelector('$selector') !== null")->getReturnValue();

            if ($exists) {
                return;
            }

            usleep(100000); // 100ms
        }

        throw new \Exception("Timeout waiting for selector: $selector");
    }
}

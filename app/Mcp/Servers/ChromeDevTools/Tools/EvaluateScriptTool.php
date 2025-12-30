<?php

namespace App\Mcp\Servers\ChromeDevTools\Tools;

use App\Mcp\Servers\ChromeDevTools\Services\BrowserService;
use App\Mcp\Servers\Tool;

class EvaluateScriptTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'evaluate-script';

    /**
     * The tool's description.
     */
    protected string $description = 'Execute JavaScript code in the context of a webpage';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'The URL of the webpage to execute script on',
                'required' => true,
            ],
            'script' => [
                'type' => 'string',
                'description' => 'The JavaScript code to execute',
                'required' => true,
            ],
            'wait_for_selector' => [
                'type' => 'string',
                'description' => 'Optional CSS selector to wait for before executing script',
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
        $script = $inputs['script'];
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

            // Execute the script
            $result = $page->evaluate($script)->getReturnValue();

            return json_encode([
                'success' => true,
                'url' => $url,
                'script' => $script,
                'result' => $result,
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
                'script' => $script,
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

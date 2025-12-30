<?php

namespace App\Mcp\Servers\ChromeDevTools\Tools;

use App\Mcp\Servers\ChromeDevTools\Services\BrowserService;
use App\Mcp\Servers\Tool;

class ScreenshotTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'screenshot';

    /**
     * The tool's description.
     */
    protected string $description = 'Take a screenshot of a webpage and save it to a file';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'The URL of the webpage to screenshot',
                'required' => true,
            ],
            'output_path' => [
                'type' => 'string',
                'description' => 'The file path where the screenshot should be saved (e.g., storage/app/screenshots/page.png)',
                'required' => false,
            ],
            'width' => [
                'type' => 'integer',
                'description' => 'Viewport width in pixels (default: 1920)',
                'required' => false,
            ],
            'height' => [
                'type' => 'integer',
                'description' => 'Viewport height in pixels (default: 1080)',
                'required' => false,
            ],
            'full_page' => [
                'type' => 'boolean',
                'description' => 'Whether to capture the full page (default: false)',
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
        $outputPath = $inputs['output_path'] ?? storage_path('app/screenshots/'.time().'.png');
        $width = $inputs['width'] ?? 1920;
        $height = $inputs['height'] ?? 1080;
        $fullPage = $inputs['full_page'] ?? false;

        try {
            // Ensure the directory exists
            $directory = dirname($outputPath);
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Use shared browser service instead of creating new browser
            $page = BrowserService::getPage();

            // Set viewport size
            $page->setViewport($width, $height)->await();

            // Navigate to URL
            $page->navigate($url)->waitForNavigation();

            // Take screenshot
            if ($fullPage) {
                $screenshot = $page->screenshot([
                    'captureBeyondViewport' => true,
                    'fullPage' => true,
                ]);
            } else {
                $screenshot = $page->screenshot();
            }

            // Save to file
            $screenshot->saveToFile($outputPath);

            return json_encode([
                'success' => true,
                'message' => 'Screenshot captured successfully',
                'url' => $url,
                'file_path' => $outputPath,
                'viewport' => ['width' => $width, 'height' => $height],
                'full_page' => $fullPage,
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

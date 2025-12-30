<?php

namespace App\Mcp\Servers\ChromeDevTools\Services;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;

/**
 * Shared browser service for ChromeDevTools MCP server.
 *
 * This service manages browser lifecycle and provides shared browser instances
 * to avoid repeatedly creating/destroying browsers across tool invocations.
 * Based on Anthropic's MCP best practices for context efficiency.
 */
class BrowserService
{
    protected static ?Browser $browser = null;

    protected static ?Page $currentPage = null;

    protected static array $config = [
        'headless' => true,
        'noSandbox' => true,
    ];

    /**
     * Get or create a browser instance.
     */
    public static function getBrowser(): Browser
    {
        if (static::$browser === null) {
            $factory = new BrowserFactory;
            static::$browser = $factory->createBrowser(static::$config);
        }

        return static::$browser;
    }

    /**
     * Get or create a page instance.
     */
    public static function getPage(): Page
    {
        if (static::$currentPage === null) {
            static::$currentPage = static::getBrowser()->createPage();
        }

        return static::$currentPage;
    }

    /**
     * Create a new page instance (for multi-page operations).
     */
    public static function createPage(): Page
    {
        return static::getBrowser()->createPage();
    }

    /**
     * Close the current page and create a new one.
     */
    public static function resetPage(): Page
    {
        if (static::$currentPage !== null) {
            try {
                static::$currentPage->close();
            } catch (\Exception $e) {
                // Page might already be closed
            }
        }

        static::$currentPage = static::getBrowser()->createPage();

        return static::$currentPage;
    }

    /**
     * Close the browser and clean up resources.
     */
    public static function closeBrowser(): void
    {
        if (static::$browser !== null) {
            try {
                static::$browser->close();
            } catch (\Exception $e) {
                // Browser might already be closed
            }

            static::$browser = null;
            static::$currentPage = null;
        }
    }

    /**
     * Set browser configuration.
     */
    public static function configure(array $config): void
    {
        static::$config = array_merge(static::$config, $config);

        // If browser is already running, close it to apply new config on next use
        if (static::$browser !== null) {
            static::closeBrowser();
        }
    }

    /**
     * Get current browser state information.
     */
    public static function getState(): array
    {
        return [
            'browser_active' => static::$browser !== null,
            'page_active' => static::$currentPage !== null,
            'config' => static::$config,
        ];
    }
}

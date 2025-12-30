<?php

namespace App\Mcp\Servers\ChromeDevTools;

use Laravel\Mcp\Server;

class ChromeDevToolsServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Chrome DevTools';

    /**
     * The MCP server's version.
     */
    protected string $version = '2.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        # Chrome DevTools MCP Server

        This server provides tools to interact with Chrome browser via the Chrome DevTools Protocol.
        You can use these tools to automate browser tasks, take screenshots, extract content,
        and perform other browser automation tasks.

        ## Architecture

        This server uses a shared browser service for efficient resource management:
        - Browser instances are reused across tool invocations
        - Reduces overhead of repeatedly launching/closing browsers
        - Maintains state between operations when needed

        ## Available Tools

        - **screenshot**: Take a screenshot of a webpage
        - **get-page-content**: Extract the HTML content of a webpage
        - **navigate**: Navigate to a URL
        - **evaluate-script**: Execute JavaScript in the page context
        - **vendor-portal-login**: Login to vendor portals and save session cookies
        - **product-data-scraper**: Scrape product data (title, SKU, price) with optional authentication

        ## Requirements

        - Chrome or Chromium browser must be installed
        - The browser will launch in headless mode by default

        ## Skills

        You can create reusable skills by combining these tools. Skills are saved in the Skills/
        directory and documented in SKILL.md for future reference.

        ## State Management

        The server maintains state in the State/ directory for:
        - Browser session information
        - Cookies and authentication
        - Cached results
        - Agent-learned patterns
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        \App\Mcp\Servers\ChromeDevTools\Tools\ScreenshotTool::class,
        \App\Mcp\Servers\ChromeDevTools\Tools\GetPageContentTool::class,
        \App\Mcp\Servers\ChromeDevTools\Tools\NavigateTool::class,
        \App\Mcp\Servers\ChromeDevTools\Tools\EvaluateScriptTool::class,
        \App\Mcp\Servers\ChromeDevTools\Tools\VendorPortalLoginTool::class,
        \App\Mcp\Servers\ChromeDevTools\Tools\ProductDataScraperTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}

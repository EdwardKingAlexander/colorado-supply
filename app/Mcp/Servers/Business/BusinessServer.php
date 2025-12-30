<?php

namespace App\Mcp\Servers\Business;

use Laravel\Mcp\Server;

class BusinessServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Business Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        # Business Server

        This server provides business-specific tools and functionality for the application.

        ## Architecture

        This server follows the progressive disclosure pattern with:
        - Tools organized in the Tools/ directory
        - Reusable skills in the Skills/ directory
        - State management in the State/ directory

        ## Available Tools

        - **fetch-competitor-pricing**: Compare competitor prices from scrape data with internal database
        - **compliance-audit**: Audit vendor compliance documents (SAM, W-9, Insurance, CAGE, DUNS, NAICS)
        - **fetch-sam-opportunities**: Fetch federal contract opportunities from SAM.gov filtered by NAICS, location, and notice types

        ## Integration with ChromeDevTools

        Business tools can access data from ChromeDevTools scraping operations:
        - Load scrape results from ChromeDevTools/State/
        - Combine browser automation with business logic
        - Automate pricing analysis and competitive intelligence

        ## Skills

        You can create reusable skills by combining tools. Skills are saved in the Skills/
        directory and documented in SKILL.md for future reference.

        ## State Management

        The server maintains state in the State/ directory for persistence across operations.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        \App\Mcp\Servers\Business\Tools\FetchCompetitorPricingTool::class,
        \App\Mcp\Servers\Business\Tools\ComplianceAuditTool::class,
        \App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool::class,
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

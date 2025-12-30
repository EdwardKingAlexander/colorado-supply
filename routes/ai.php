<?php

use App\Mcp\Servers\Business\BusinessServer;
use App\Mcp\Servers\ChromeDevTools\ChromeDevToolsServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI Routes
|--------------------------------------------------------------------------
|
| Here you can register MCP (Model Context Protocol) servers that expose
| tools, resources, and prompts to AI assistants like Claude Code.
|
| These servers follow Anthropic's MCP best practices with:
| - Progressive disclosure through organized directory structure
| - Shared services for efficient resource management
| - State persistence for agent learning
| - Reusable skills for complex operations
|
*/

Mcp::web('mcp/business', BusinessServer::class);
Mcp::web('mcp/chrome-devtools', ChromeDevToolsServer::class);

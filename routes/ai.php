<?php

use App\Mcp\Servers\BusinessServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI Routes
|--------------------------------------------------------------------------
|
| Here you can register MCP (Model Context Protocol) servers that expose
| tools, resources, and prompts to AI assistants like Claude Code.
|
*/

Mcp::web('mcp/business', BusinessServer::class);

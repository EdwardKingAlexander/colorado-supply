<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool as BaseTool;

abstract class Tool extends BaseTool
{
    /**
     * Bridge legacy execute implementations to the MCP v2 handle contract.
     *
     * @return string JSON-encoded response payload.
     */
    abstract protected function execute(array $inputs): string;

    public function handle(Request $request): Response
    {
        return Response::text($this->execute($request->all()));
    }
}

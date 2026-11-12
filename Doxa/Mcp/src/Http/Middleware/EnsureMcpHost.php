<?php

namespace Doxa\Mcp\Http\Middleware;

use Closure;
use Doxa\Mcp\Libraries\McpHost;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пропускает запросы OAuth и /mcp только на хосте, продукту которого задан MCP-сервер (doxa_mcp.servers); иначе 404.
 */
class EnsureMcpHost
{
    public function __construct(
        private readonly McpHost $host,
    ) {}

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->host->serverClass() === null) {
            abort(404);
        }

        return $next($request);
    }
}

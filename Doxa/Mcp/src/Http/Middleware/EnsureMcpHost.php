<?php

namespace Doxa\Mcp\Http\Middleware;

use Closure;
use Doxa\Mcp\Libraries\McpHost;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пропускает OAuth и /mcp/{ключ} только на хосте с заданными MCP-серверами; неизвестный ключ пути — 404.
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
        if (!$this->host->hasServers()) {
            abort(404);
        }

        $mcpKey = $this->host->mcpKey();
        if ($mcpKey !== null && $this->host->serverClass() === null) {
            abort(404);
        }

        return $next($request);
    }
}

<?php

namespace Doxa\Mcp\Http\Middleware;

use Closure;
use Doxa\Mcp\Libraries\McpCallLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пишет вызов /mcp в журнал (McpCallLog). Ставится после AuthenticateMcpToken: токен уже в атрибутах запроса.
 */
class LogMcpCall
{
    public function __construct(
        private readonly McpCallLog $log,
    ) {}

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);

        $message = json_decode((string) $request->getContent(), true);
        if (!is_array($message) || !isset($message['method']) || !is_string($message['method'])) {
            return $response;
        }

        $params = is_array($message['params'] ?? null) ? $message['params'] : [];
        $tool = null;
        $arguments = null;
        if ($message['method'] === 'tools/call') {
            $tool = is_string($params['name'] ?? null) ? $params['name'] : null;
            $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : null;
        }

        $token = $request->attributes->get(AuthenticateMcpToken::TOKEN_ATTRIBUTE);
        $this->log->write(
            tokenId: (int) $token->id,
            userId: (int) $token->user_id,
            product: $token->product,
            rpcMethod: $message['method'],
            tool: $tool,
            arguments: $arguments,
            httpStatus: $response->getStatusCode(),
            isError: $this->isError($response),
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return $response;
    }

    /**
     * Возвращает true, если ответ — ошибка: HTTP 4xx/5xx, ошибка JSON-RPC или результат инструмента с isError.
     */
    private function isError(Response $response): bool
    {
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        $body = json_decode((string) $response->getContent(), true);
        if (!is_array($body)) {
            return false;
        }

        return isset($body['error']) || ($body['result']['isError'] ?? false) === true;
    }
}

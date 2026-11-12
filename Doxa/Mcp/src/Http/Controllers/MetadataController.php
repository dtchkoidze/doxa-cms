<?php

namespace Doxa\Mcp\Http\Controllers;

use Doxa\Mcp\Libraries\McpHost;
use Illuminate\Http\JsonResponse;

/**
 * Служебные документы OAuth для MCP-клиента: защищённый ресурс (RFC 9728) и сервер авторизации (RFC 8414).
 */
class MetadataController
{
    public function __construct(
        private readonly McpHost $host,
    ) {}

    /**
     * Отдаёт документ защищённого ресурса: адрес /mcp и сервер авторизации.
     */
    public function protectedResource(): JsonResponse
    {
        return response()->json([
            'resource' => $this->host->resourceUrl(),
            'authorization_servers' => [$this->host->issuer()],
            'scopes_supported' => [config('doxa_mcp.scope')],
            'bearer_methods_supported' => ['header'],
        ]);
    }

    /**
     * Отдаёт документ сервера авторизации: адреса authorize, token, register и поддерживаемые режимы.
     */
    public function authorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer' => $this->host->issuer(),
            'authorization_endpoint' => url('/oauth/authorize'),
            'token_endpoint' => url('/oauth/token'),
            'registration_endpoint' => url('/oauth/register'),
            'scopes_supported' => [config('doxa_mcp.scope')],
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'code_challenge_methods_supported' => ['S256'],
        ]);
    }
}

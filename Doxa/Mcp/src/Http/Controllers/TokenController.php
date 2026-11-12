<?php

namespace Doxa\Mcp\Http\Controllers;

use Doxa\Mcp\Libraries\McpHost;
use Doxa\Mcp\Libraries\OAuthTokens;
use Doxa\Mcp\Libraries\TokenGrant;
use Illuminate\Http\JsonResponse;

/**
 * Token endpoint OAuth: обмен кода на токены и продление токенов.
 */
class TokenController
{
    public function __construct(
        private readonly OAuthTokens $tokens,
        private readonly McpHost $host,
    ) {}

    /**
     * Отдаёт токены по grant_type authorization_code или refresh_token; ошибка — 400 с кодом RFC 6749.
     */
    public function token(): JsonResponse
    {
        $grantType = request('grant_type');
        if ($grantType === 'authorization_code') {
            $grant = $this->tokens->exchangeCode(request()->all(), $this->host->context());
        } elseif ($grantType === 'refresh_token') {
            $grant = $this->tokens->refresh(request()->all(), $this->host->context());
        } else {
            $grant = new TokenGrant(error: 'unsupported_grant_type', errorDescription: 'grant_type must be authorization_code or refresh_token');
        }

        $headers = [
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ];

        if ($grant->issued()) {
            return response()->json($grant->payload, 200, $headers);
        }

        return response()->json([
            'error' => $grant->error,
            'error_description' => $grant->errorDescription,
        ], 400, $headers);
    }
}

<?php

namespace Doxa\Mcp\Http\Middleware;

use Closure;
use Doxa\Mcp\Libraries\McpHost;
use Doxa\Mcp\Libraries\McpUsers;
use Doxa\Mcp\Libraries\OAuthTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Вход на /mcp по access-токену OAuth: токен действует, выдан для продукта этого хоста, пользователь активен.
 * Пользователь ставится в Auth на время запроса (без сессии); строка токена — в атрибуте запроса TOKEN_ATTRIBUTE.
 * Без токена или с плохим токеном — 401 с адресом документа защищённого ресурса (клиент начинает OAuth).
 */
class AuthenticateMcpToken
{
    public const TOKEN_ATTRIBUTE = 'doxa_mcp_token';

    public function __construct(
        private readonly OAuthTokens $tokens,
        private readonly McpUsers $users,
        private readonly McpHost $host,
    ) {}

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if ($plain === null || $plain === '') {
            return $this->unauthorized(null);
        }

        $token = $this->tokens->findActiveByAccessToken($plain);
        if ($token === null || $token->product !== $this->host->context()) {
            return $this->unauthorized('invalid_token');
        }

        $user = $this->users->findActive((int) $token->user_id);
        if ($user === null) {
            return $this->unauthorized('invalid_token');
        }

        Auth::setUser($user);
        $this->tokens->markUsed((int) $token->id);
        $request->attributes->set(self::TOKEN_ATTRIBUTE, $token);

        return $next($request);
    }

    /**
     * Возвращает 401 с WWW-Authenticate (RFC 9728): адрес документа ресурса и код ошибки, если токен был.
     */
    private function unauthorized(?string $error): Response
    {
        $challenge = 'Bearer resource_metadata="' . $this->host->protectedResourceMetadataUrl() . '"';
        if ($error !== null) {
            $challenge .= ', error="' . $error . '"';
        }

        return response()->json([
            'error' => $error ?? 'unauthorized',
        ], 401, [
            'WWW-Authenticate' => $challenge,
        ]);
    }
}

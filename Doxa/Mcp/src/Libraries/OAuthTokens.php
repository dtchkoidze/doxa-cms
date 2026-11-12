<?php

namespace Doxa\Mcp\Libraries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Токены MCP: обмен кода на токены, продление с заменой refresh-токена, поиск по access-токену.
 * В базе только sha256; токен привязан к пользователю, клиенту и продукту хоста.
 */
class OAuthTokens
{
    public const TABLE = 'mcp_oauth_tokens';

    public function __construct(
        private readonly AuthorizationCodes $codes,
        private readonly OAuthClients $clients,
        private readonly McpUsers $users,
        private readonly McpHost $host,
    ) {}

    /**
     * Обменивает код авторизации на токены (grant_type=authorization_code) и возвращает итог.
     *
     * @param array<string, mixed> $input тело запроса к token endpoint
     */
    public function exchangeCode(array $input, string $product): TokenGrant
    {
        $code = $this->stringInput($input, 'code');
        $clientId = $this->stringInput($input, 'client_id');
        $redirectUri = $this->stringInput($input, 'redirect_uri');
        $verifier = $this->stringInput($input, 'code_verifier');
        if ($code === null || $clientId === null || $redirectUri === null || $verifier === null) {
            return new TokenGrant(error: 'invalid_request', errorDescription: 'code, client_id, redirect_uri and code_verifier are required');
        }

        $row = $this->codes->consume($code);
        if ($row === null) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'code is invalid or already used');
        }
        if (now()->greaterThan($row->expires_at)) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'code expired');
        }
        if ($row->client_id !== $clientId || $row->redirect_uri !== $redirectUri || $row->product !== $product) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'code was issued for another client, redirect_uri or host');
        }
        if (!$this->pkceMatches($verifier, $row->code_challenge)) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'code_verifier does not match code_challenge');
        }
        $resource = $this->stringInput($input, 'resource');
        if ($resource !== null && rtrim($resource, '/') !== $this->host->resourceUrl()) {
            return new TokenGrant(error: 'invalid_target', errorDescription: 'resource must be ' . $this->host->resourceUrl());
        }
        if ($this->users->findActive((int) $row->user_id) === null) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'user is not active');
        }

        $access = Str::random(64);
        $refresh = Str::random(64);
        DB::table(self::TABLE)->insert([
            'client_id' => $clientId,
            'user_id' => $row->user_id,
            'product' => $product,
            'scope' => $row->scope,
            'access_token_hash' => hash('sha256', $access),
            'access_expires_at' => now()->addMinutes(config('doxa_mcp.access_ttl_minutes')),
            'refresh_token_hash' => hash('sha256', $refresh),
            'refresh_expires_at' => now()->addDays(config('doxa_mcp.refresh_ttl_days')),
            'created_at' => now(),
        ]);
        $this->clients->markUsed($clientId);

        return new TokenGrant(payload: $this->payload($access, $refresh, $row->scope));
    }

    /**
     * Продлевает токены по refresh-токену (grant_type=refresh_token): новые access и refresh, старые перестают работать.
     *
     * @param array<string, mixed> $input тело запроса к token endpoint
     */
    public function refresh(array $input, string $product): TokenGrant
    {
        $refreshPlain = $this->stringInput($input, 'refresh_token');
        $clientId = $this->stringInput($input, 'client_id');
        if ($refreshPlain === null || $clientId === null) {
            return new TokenGrant(error: 'invalid_request', errorDescription: 'refresh_token and client_id are required');
        }

        $row = DB::table(self::TABLE)
            ->where('refresh_token_hash', hash('sha256', $refreshPlain))
            ->whereNull('revoked_at')
            ->first();
        if (!$row || now()->greaterThan($row->refresh_expires_at)) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'refresh_token is invalid or expired');
        }
        if ($row->client_id !== $clientId || $row->product !== $product) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'refresh_token was issued for another client or host');
        }
        if ($this->users->findActive((int) $row->user_id) === null) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'user is not active');
        }

        $access = Str::random(64);
        $refresh = Str::random(64);
        $rotated = DB::table(self::TABLE)
            ->where('id', $row->id)
            ->where('refresh_token_hash', $row->refresh_token_hash)
            ->whereNull('revoked_at')
            ->update([
                'access_token_hash' => hash('sha256', $access),
                'access_expires_at' => now()->addMinutes(config('doxa_mcp.access_ttl_minutes')),
                'refresh_token_hash' => hash('sha256', $refresh),
                'refresh_expires_at' => now()->addDays(config('doxa_mcp.refresh_ttl_days')),
            ]);
        if ($rotated !== 1) {
            return new TokenGrant(error: 'invalid_grant', errorDescription: 'refresh_token was already used');
        }
        $this->clients->markUsed($clientId);

        return new TokenGrant(payload: $this->payload($access, $refresh, $row->scope));
    }

    /**
     * Возвращает действующую строку токена по открытому access-токену; null — токена нет, он отозван или истёк.
     *
     * @return object{id: int, user_id: int, client_id: string, product: string, scope: string}|null
     */
    public function findActiveByAccessToken(string $plain): ?object
    {
        $row = DB::table(self::TABLE)
            ->where('access_token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->where('access_expires_at', '>', now())
            ->first();

        return $row ?: null;
    }

    /**
     * Отмечает использование токена.
     */
    public function markUsed(int $tokenId): void
    {
        DB::table(self::TABLE)->where('id', $tokenId)->update(['last_used_at' => now()]);
    }

    /**
     * Возвращает ответ token endpoint.
     *
     * @return array{access_token: string, token_type: string, expires_in: int, refresh_token: string, scope: string}
     */
    private function payload(string $access, string $refresh, string $scope): array
    {
        return [
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => config('doxa_mcp.access_ttl_minutes') * 60,
            'refresh_token' => $refresh,
            'scope' => $scope,
        ];
    }

    /**
     * Возвращает true, если code_verifier соответствует code_challenge (S256).
     */
    private function pkceMatches(string $verifier, string $challenge): bool
    {
        if (preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier) !== 1) {
            return false;
        }

        $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return hash_equals($challenge, $computed);
    }

    /**
     * Возвращает непустую строку параметра или null.
     *
     * @param array<string, mixed> $input
     */
    private function stringInput(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}

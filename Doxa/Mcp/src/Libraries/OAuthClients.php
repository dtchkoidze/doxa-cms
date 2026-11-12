<?php

namespace Doxa\Mcp\Libraries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OAuth-клиенты MCP: регистрация (RFC 7591, клиент регистрирует себя сам) и проверка redirect_uri.
 * Клиенты публичные: секрета нет, защита — PKCE и белый список redirect_uri (doxa_mcp.redirect_uri_patterns).
 */
class OAuthClients
{
    public const TABLE = 'mcp_oauth_clients';

    /**
     * Возвращает true, если redirect_uri подходит под один из шаблонов doxa_mcp.redirect_uri_patterns.
     */
    public function isAllowedRedirectUri(string $uri): bool
    {
        foreach (config('doxa_mcp.redirect_uri_patterns') as $pattern) {
            if (preg_match($pattern, $uri) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Регистрирует клиента и возвращает его client_id.
     *
     * @param list<string> $redirectUris уже проверенные isAllowedRedirectUri()
     */
    public function register(array $redirectUris, ?string $clientName): string
    {
        $clientId = Str::random(40);

        DB::table(self::TABLE)->insert([
            'client_id' => $clientId,
            'client_name' => $clientName,
            'redirect_uris' => json_encode(array_values($redirectUris), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);

        return $clientId;
    }

    /**
     * Возвращает клиента по client_id или null; redirect_uris — уже массив.
     *
     * @return object{client_id: string, client_name: string|null, redirect_uris: list<string>}|null
     */
    public function find(string $clientId): ?object
    {
        $client = DB::table(self::TABLE)->where('client_id', $clientId)->first();
        if (!$client) {
            return null;
        }

        $client->redirect_uris = json_decode($client->redirect_uris, true, flags: JSON_THROW_ON_ERROR);

        return $client;
    }

    /**
     * Возвращает true, если redirect_uri зарегистрирован у клиента (точное совпадение).
     *
     * @param object{redirect_uris: list<string>} $client
     */
    public function hasRedirectUri(object $client, string $uri): bool
    {
        return in_array($uri, $client->redirect_uris, true);
    }

    /**
     * Отмечает использование клиента (выдача или продление токена).
     */
    public function markUsed(string $clientId): void
    {
        DB::table(self::TABLE)->where('client_id', $clientId)->update(['last_used_at' => now()]);
    }
}

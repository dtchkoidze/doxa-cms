<?php

namespace Doxa\Mcp\Libraries;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Коды авторизации OAuth: одноразовые, короткоживущие, в базе только sha256.
 */
class AuthorizationCodes
{
    public const TABLE = 'mcp_oauth_codes';

    /**
     * Выдаёт код для подтверждённого запроса авторизации и возвращает его открытое значение.
     */
    public function issue(AuthorizationRequest $authRequest, int $userId, string $product): string
    {
        $plain = Str::random(64);

        DB::table(self::TABLE)->insert([
            'code_hash' => hash('sha256', $plain),
            'client_id' => $authRequest->client->client_id,
            'user_id' => $userId,
            'product' => $product,
            'redirect_uri' => $authRequest->redirectUri,
            'code_challenge' => $authRequest->codeChallenge,
            'scope' => $authRequest->scope,
            'resource' => $authRequest->resource,
            'expires_at' => now()->addMinutes(config('doxa_mcp.code_ttl_minutes')),
            'created_at' => now(),
        ]);

        return $plain;
    }

    /**
     * Погашает код и возвращает его строку; null — кода нет или он уже использован.
     * Срок, клиента и PKCE проверяет вызывающий.
     *
     * @return object{user_id: int, client_id: string, product: string, redirect_uri: string, code_challenge: string, scope: string, resource: string|null, expires_at: string}|null
     */
    public function consume(string $plainCode): ?object
    {
        $hash = hash('sha256', $plainCode);
        $row = DB::table(self::TABLE)->where('code_hash', $hash)->whereNull('used_at')->first();
        if (!$row) {
            return null;
        }

        $marked = DB::table(self::TABLE)->where('id', $row->id)->whereNull('used_at')->update(['used_at' => now()]);
        if ($marked !== 1) {
            return null;
        }

        return $row;
    }
}

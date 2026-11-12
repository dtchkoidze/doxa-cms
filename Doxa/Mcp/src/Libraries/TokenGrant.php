<?php

namespace Doxa\Mcp\Libraries;

/**
 * Итог запроса к token endpoint. Чек — issued(); при отказе — error / errorDescription (RFC 6749, 5.2).
 */
class TokenGrant
{
    /**
     * @param array{access_token: string, token_type: string, expires_in: int, refresh_token: string, scope: string}|null $payload ответ клиенту при выдаче
     */
    public function __construct(
        public readonly ?array $payload = null,
        public readonly ?string $error = null,
        public readonly ?string $errorDescription = null,
    ) {}

    /**
     * Возвращает true, если токены выданы.
     */
    public function issued(): bool
    {
        return $this->payload !== null;
    }
}

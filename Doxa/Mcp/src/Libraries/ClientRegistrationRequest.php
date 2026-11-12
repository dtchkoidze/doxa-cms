<?php

namespace Doxa\Mcp\Libraries;

/**
 * Проверка запроса регистрации клиента (RFC 7591). Чек — isValid(); при отказе причина в error / errorDescription.
 */
class ClientRegistrationRequest
{
    /** @var list<string> */
    public array $redirectUris = [];

    public ?string $clientName = null;

    /** Код ошибки RFC 7591 (invalid_redirect_uri, invalid_client_metadata) при отказе. */
    public ?string $error = null;

    public ?string $errorDescription = null;

    private bool $valid;

    /**
     * @param array<string, mixed> $input тело запроса регистрации
     */
    public function __construct(
        private readonly array $input,
        private readonly OAuthClients $clients,
    ) {
        $this->valid = $this->check();
    }

    /**
     * Возвращает true, если клиента можно регистрировать.
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    private function check(): bool
    {
        $uris = $this->input['redirect_uris'] ?? null;
        if (!is_array($uris) || $uris === []) {
            return $this->deny('invalid_redirect_uri', 'redirect_uris is required');
        }

        foreach ($uris as $uri) {
            if (!is_string($uri) || !$this->clients->isAllowedRedirectUri($uri)) {
                return $this->deny('invalid_redirect_uri', 'redirect_uri is not allowed: ' . (is_string($uri) ? $uri : gettype($uri)));
            }
            $this->redirectUris[] = $uri;
        }

        // token_endpoint_auth_method клиента не проверяем: клиент всегда публичный, в ответе регистрации — none
        $name = $this->input['client_name'] ?? null;
        if ($name !== null) {
            if (!is_string($name)) {
                return $this->deny('invalid_client_metadata', 'client_name must be a string');
            }
            $this->clientName = mb_substr(trim($name), 0, 255);
        }

        return true;
    }

    private function deny(string $error, string $description): bool
    {
        $this->error = $error;
        $this->errorDescription = $description;

        return false;
    }
}

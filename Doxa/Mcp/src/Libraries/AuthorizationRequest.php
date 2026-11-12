<?php

namespace Doxa\Mcp\Libraries;

/**
 * Проверка запроса авторизации OAuth (authorization code + PKCE S256). Чек — isValid().
 * При отказе: error / errorDescription и canRedirectError() — можно ли вернуть ошибку клиенту на redirect_uri
 * (только если клиент найден и redirect_uri у него зарегистрирован).
 */
class AuthorizationRequest
{
    /** @var object{client_id: string, client_name: string|null, redirect_uris: list<string>}|null */
    public ?object $client = null;

    public ?string $redirectUri = null;

    public ?string $state = null;

    public ?string $codeChallenge = null;

    public ?string $scope = null;

    public ?string $resource = null;

    /** Код ошибки OAuth при отказе. */
    public ?string $error = null;

    public ?string $errorDescription = null;

    private bool $valid;

    private bool $redirectTrusted = false;

    /**
     * @param array<string, mixed> $input параметры запроса авторизации
     */
    public function __construct(
        private readonly array $input,
        private readonly OAuthClients $clients,
        private readonly McpHost $host,
    ) {
        $this->valid = $this->check();
    }

    /**
     * Возвращает true, если запрос авторизации корректен.
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Возвращает true, если ошибку можно вернуть клиенту редиректом на redirect_uri.
     */
    public function canRedirectError(): bool
    {
        return $this->redirectTrusted;
    }

    /**
     * Возвращает адрес возврата клиенту с параметрами ответа и state запроса.
     *
     * @param array<string, string> $params code или error / error_description
     */
    public function redirectUrl(array $params): string
    {
        if ($this->state !== null) {
            $params['state'] = $this->state;
        }

        $separator = str_contains($this->redirectUri, '?') ? '&' : '?';

        return $this->redirectUri . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Возвращает параметры запроса для повторной отправки формой подтверждения.
     *
     * @return array<string, string>
     */
    public function formParams(): array
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client->client_id,
            'redirect_uri' => $this->redirectUri,
            'code_challenge' => $this->codeChallenge,
            'code_challenge_method' => 'S256',
            'scope' => $this->scope,
        ];
        if ($this->state !== null) {
            $params['state'] = $this->state;
        }
        if ($this->resource !== null) {
            $params['resource'] = $this->resource;
        }

        return $params;
    }

    private function check(): bool
    {
        $clientId = $this->stringInput('client_id');
        if ($clientId === null) {
            return $this->deny('invalid_request', 'client_id is required');
        }
        $this->client = $this->clients->find($clientId);
        if ($this->client === null) {
            return $this->deny('invalid_client', 'unknown client_id');
        }

        $redirectUri = $this->stringInput('redirect_uri');
        if ($redirectUri === null || !$this->clients->hasRedirectUri($this->client, $redirectUri)) {
            return $this->deny('invalid_request', 'redirect_uri is not registered for this client');
        }
        $this->redirectUri = $redirectUri;
        $this->redirectTrusted = true;
        $this->state = $this->stringInput('state');

        if ($this->stringInput('response_type') !== 'code') {
            return $this->deny('unsupported_response_type', 'only response_type=code is supported');
        }

        $this->codeChallenge = $this->stringInput('code_challenge');
        if ($this->codeChallenge === null || preg_match('/^[A-Za-z0-9_-]{43,128}$/', $this->codeChallenge) !== 1) {
            return $this->deny('invalid_request', 'code_challenge is required');
        }
        if ($this->stringInput('code_challenge_method') !== 'S256') {
            return $this->deny('invalid_request', 'code_challenge_method must be S256');
        }

        $scope = config('doxa_mcp.scope');
        $requestedScope = $this->stringInput('scope');
        if ($requestedScope !== null) {
            foreach (preg_split('/\s+/', $requestedScope, -1, PREG_SPLIT_NO_EMPTY) as $item) {
                if ($item !== $scope) {
                    return $this->deny('invalid_scope', 'unsupported scope: ' . $item);
                }
            }
        }
        $this->scope = $scope;

        $this->resource = $this->stringInput('resource');
        if ($this->resource !== null && rtrim($this->resource, '/') !== $this->host->resourceUrl()) {
            return $this->deny('invalid_target', 'resource must be ' . $this->host->resourceUrl());
        }

        return true;
    }

    /**
     * Возвращает непустую строку параметра или null.
     */
    private function stringInput(string $key): ?string
    {
        $value = $this->input[$key] ?? null;
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function deny(string $error, string $description): bool
    {
        $this->error = $error;
        $this->errorDescription = $description;

        return false;
    }
}

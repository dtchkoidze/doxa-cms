<?php

namespace Doxa\Mcp\Http\Controllers;

use Doxa\Mcp\Libraries\ClientRegistrationRequest;
use Doxa\Mcp\Libraries\OAuthClients;
use Illuminate\Http\JsonResponse;

/**
 * Регистрация OAuth-клиента (RFC 7591): ChatGPT регистрирует себя сам перед первым входом.
 */
class RegisterController
{
    public function __construct(
        private readonly OAuthClients $clients,
    ) {}

    /**
     * Регистрирует публичного клиента и отдаёт его client_id; 400 — redirect_uri не из разрешённых.
     */
    public function register(): JsonResponse
    {
        $registration = new ClientRegistrationRequest(request()->all(), $this->clients);
        if (!$registration->isValid()) {
            return response()->json([
                'error' => $registration->error,
                'error_description' => $registration->errorDescription,
            ], 400);
        }

        $clientId = $this->clients->register($registration->redirectUris, $registration->clientName);

        return response()->json([
            'client_id' => $clientId,
            'client_id_issued_at' => now()->getTimestamp(),
            'client_name' => $registration->clientName,
            'redirect_uris' => $registration->redirectUris,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
        ], 201);
    }
}

<?php

namespace Doxa\Mcp\Http\Controllers;

use Doxa\Mcp\Libraries\AuthorizationCodes;
use Doxa\Mcp\Libraries\AuthorizationRequest;
use Doxa\Mcp\Libraries\McpHost;
use Doxa\Mcp\Libraries\McpUsers;
use Doxa\Mcp\Libraries\OAuthClients;
use Doxa\User\Libraries\Registration as REG;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Страница подтверждения доступа MCP-клиента к аккаунту: вход через обычный логин сайта, затем «Разрешить» / «Отклонить».
 */
class AuthorizeController
{
    public function __construct(
        private readonly OAuthClients $clients,
        private readonly AuthorizationCodes $codes,
        private readonly McpUsers $users,
        private readonly McpHost $host,
    ) {}

    /**
     * Показывает страницу подтверждения; гость уходит на вход с возвратом сюда; плохой запрос — ошибка клиенту или страница ошибки.
     */
    public function show(): View|RedirectResponse
    {
        $authRequest = new AuthorizationRequest(request()->query(), $this->clients, $this->host);
        if (!$authRequest->isValid()) {
            return $this->rejected($authRequest);
        }

        if (!Auth::check()) {
            return redirect()->to($this->loginUrl(request()->getRequestUri()));
        }

        $user = Auth::user();
        if (!$this->users->isActive($user)) {
            return redirect()->away($authRequest->redirectUrl(['error' => 'access_denied']));
        }

        REG::init();

        return view('doxa-mcp::authorize', [
            'wrapper' => REG::authWrapper(),
            'title' => vocab('mcp_consent_title'),
            'clientName' => $authRequest->client->client_name ?? $authRequest->client->client_id,
            'userName' => $this->userLabel($user),
            'switchAccountUrl' => route('auth.logout') . '?return=' . rawurlencode($this->loginUrl(request()->getRequestUri())),
            'params' => $authRequest->formParams(),
        ]);
    }

    /**
     * Принимает решение пользователя: «Разрешить» — код авторизации клиенту, иначе — access_denied.
     */
    public function decide(): RedirectResponse|View
    {
        $authRequest = new AuthorizationRequest(request()->all(), $this->clients, $this->host);
        if (!$authRequest->isValid()) {
            return $this->rejected($authRequest);
        }

        if (!Auth::check()) {
            return redirect()->to($this->loginUrl('/oauth/authorize?' . http_build_query($authRequest->formParams(), '', '&', PHP_QUERY_RFC3986)));
        }

        if (request('decision') !== 'allow' || !$this->users->isActive(Auth::user())) {
            return redirect()->away($authRequest->redirectUrl(['error' => 'access_denied']));
        }

        $code = $this->codes->issue($authRequest, (int) Auth::id(), $this->host->context());

        return redirect()->away($authRequest->redirectUrl(['code' => $code]));
    }

    /**
     * Возвращает ошибку запроса: редиректом клиенту, если redirect_uri проверен, иначе страницей ошибки.
     */
    private function rejected(AuthorizationRequest $authRequest): RedirectResponse|View
    {
        if ($authRequest->canRedirectError()) {
            return redirect()->away($authRequest->redirectUrl([
                'error' => $authRequest->error,
                'error_description' => $authRequest->errorDescription,
            ]));
        }

        REG::init();

        return view('doxa-mcp::error', [
            'wrapper' => REG::authWrapper(),
            'title' => vocab('mcp_consent_error_title'),
            'error' => $authRequest->error,
            'errorDescription' => $authRequest->errorDescription,
        ]);
    }

    /**
     * Возвращает адрес входа с возвратом на $returnPath (doxa_mcp.login_url).
     */
    private function loginUrl(string $returnPath): string
    {
        return str_replace('{return}', rawurlencode($returnPath), config('doxa_mcp.login_url'));
    }

    /**
     * Возвращает подпись пользователя для страницы: имя и email, что есть.
     */
    private function userLabel(object $user): string
    {
        $parts = array_filter([trim((string) $user->name), trim((string) $user->email)], fn (string $part) => $part !== '');

        return implode(' · ', $parts);
    }
}

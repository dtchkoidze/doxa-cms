<?php

namespace Doxa\User\Http\Middleware;

use Closure;
use Doxa\User\Libraries\AuthSessionService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Если нет session-user — поднимает Auth из mobile Bearer / X-Api-Token.
 * Для web: отозванный user_auth_sessions ряд снимает Auth и remember-cookie.
 */
class ResolveAuthSession
{
    public function __construct(
        private readonly AuthSessionService $authSessions,
    ) {}

    /**
     * Проверяет web/mobile auth-сессию: отозванный web — logout, mobile — 401.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $userId = (int) Auth::id();
            if ($userId > 0 && $request->hasSession()) {
                $row = $this->authSessions->findWebSessionForRequest($userId);
                if ($row !== null && $row->revoked_at !== null) {
                    $this->logoutRevokedWebSession($request);

                    return $next($request);
                }
                if ($row !== null) {
                    $this->authSessions->markWebSessionUsed($row);
                }
            }

            return $next($request);
        }

        $plain = $this->authSessions->plainTokenFromRequest();
        if ($plain === null) {
            return $next($request);
        }

        $session = $this->authSessions->findActiveMobileSession($plain);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or revoked token',
            ], 401);
        }

        $user = $this->retrieveUser((int) $session->user_id);
        if ($user === null || !$this->userIsActive($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or revoked token',
            ], 401);
        }

        Auth::setUser($user);
        $this->authSessions->markSessionUsed((int) $session->id);

        return $next($request);
    }

    /**
     * Снимает Auth текущей web-сессии и remember-cookie (устройство уже отозвано в списке).
     */
    private function logoutRevokedWebSession(Request $request): void
    {
        Auth::logoutCurrentDevice();
        Cookie::queue(Cookie::forget(Auth::guard()->getRecallerName()));

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Возвращает пользователя провайдера web-guard по id.
     */
    private function retrieveUser(int $userId): ?Authenticatable
    {
        $provider = Auth::createUserProvider(config('auth.guards.web.provider', 'users'));
        if ($provider === null) {
            return null;
        }

        $user = $provider->retrieveById($userId);

        return $user instanceof Authenticatable ? $user : null;
    }

    /**
     * Как в password/social login: isActive() если метод есть на модели.
     */
    private function userIsActive(Authenticatable $user): bool
    {
        if (method_exists($user, 'isActive')) {
            return (bool) $user->isActive();
        }

        return true;
    }
}

<?php

namespace Doxa\User\Http\Controllers\Auth;

use Doxa\User\Libraries\AuthSessionService;
use Doxa\User\Libraries\Registration as REG;
use Doxa\User\Libraries\TwoFactorService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Mobile login / logout по user_auth_sessions.mobile_token.
 * Login также поднимает web Auth session — иначе HTML-навигация на /welcome остаётся гостем.
 */
class MobileAuthController
{
    public function __construct(
        private readonly AuthSessionService $authSessions,
        private readonly TwoFactorService $twoFactor,
    ) {}

    /**
     * Принимает email+password, возвращает plain token один раз.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $email = trim((string) request('email'));
        $password = (string) request('password');

        if ($email === '' || $password === '') {
            return response()->json([
                'success' => false,
                'message' => 'Email and password are required',
            ], 422);
        }

        $provider = Auth::createUserProvider(config('auth.guards.web.provider', 'users'));
        if ($provider === null) {
            return response()->json([
                'success' => false,
                'message' => 'Auth provider missing',
            ], 500);
        }

        $user = $provider->retrieveByCredentials(['email' => $email]);
        if (!$user instanceof Authenticatable) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$provider->validateCredentials($user, ['password' => $password])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (method_exists($user, 'isActive') && !$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        REG::init();
        $context = REG::authSessionContext();
        if ($context === null || $context === '') {
            return response()->json([
                'success' => false,
                'message' => 'Auth session context required',
            ], 422);
        }

        if ($this->twoFactor->hasConfirmedMethod((int) $user->getAuthIdentifier())) {
            $this->twoFactor->storePendingLogin((int) $user->getAuthIdentifier(), false, 'mobile_token');

            return response()->json([
                'success' => true,
                'needs_2fa' => true,
                'redirect' => route('auth.two_factor'),
            ]);
        }

        // Session cookie нужна для HTML (/welcome): document load не шлёт Bearer из localStorage
        Auth::login($user);
        REG::setUserFromAuth();
        REG::recordLoginArtifacts();

        try {
            $token = $this->authSessions->issueMobileToken((int) $user->getAuthIdentifier(), $context);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'redirect' => (string) config('user.auth_sessions.auth_success_url', '/welcome'),
        ]);
    }

    /**
     * Отзывает mobile_token текущего запроса и сбрасывает web-session, если есть.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $tokenRevoked = $this->authSessions->revokeMobileTokenFromRequest();
        $sessionCleared = false;

        if (Auth::check()) {
            $this->authSessions->revokeCurrentWebSession();
            Auth::logout();
            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }
            $sessionCleared = true;
        }

        $ok = $tokenRevoked || $sessionCleared;

        return response()->json([
            'success' => $ok,
        ], $ok ? 200 : 400);
    }
}

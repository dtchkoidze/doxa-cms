<?php

namespace Doxa\User\Http\Controllers\TwoFactor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Doxa\User\Libraries\AuthSessionService;
use Doxa\User\Libraries\Registration as REG;
use Doxa\User\Libraries\TwoFactorService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Challenge 2FA до выдачи сессии (текущий домен / product).
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AuthSessionService $authSessions,
    ) {
    }

    /**
     * Страница ввода кода. Pending нет — на login текущего хоста.
     */
    public function page()
    {
        if (Auth::check()) {
            REG::init();

            return redirect(REG::getSuccessAuthUrl());
        }

        $state = $this->twoFactor->challengeState();
        if (is_string($state)) {
            return redirect()->route('auth.login');
        }

        REG::init();

        return view('user::auth.two-factor', [
            'wrapper' => REG::authWrapper(),
            'title' => 'Two-factor authentication',
            'challenge' => $state,
        ]);
    }

    /**
     * Возвращает текущий канал challenge.
     */
    public function state(): JsonResponse
    {
        $state = $this->twoFactor->challengeState();
        if (is_string($state)) {
            return response()->json(['success' => false, 'error' => $state], 422);
        }

        return response()->json(['success' => true, 'challenge' => $state]);
    }

    /**
     * Переключает канал (письмо только для email/backup).
     */
    public function switchChannel(): JsonResponse
    {
        $result = $this->twoFactor->switchChallengeChannel((string) request('channel'));
        if (is_string($result)) {
            return response()->json(['success' => false, 'error' => $result], 422);
        }

        return response()->json(['success' => true, 'challenge' => $result]);
    }

    /**
     * Проверяет код; логин и session product — здесь, у вызывающего.
     */
    public function verify(): JsonResponse
    {
        $result = $this->twoFactor->verifyChallenge((string) request('code'));
        if (!$result['ok']) {
            $payload = [
                'success' => false,
                'error' => $result['error'],
            ];
            if (isset($result['retry_after'])) {
                $payload['retry_after'] = $result['retry_after'];
            }

            return response()->json($payload, 422);
        }

        $user = User::query()->find($result['user_id']);
        if (!$user instanceof Authenticatable) {
            return response()->json(['success' => false, 'error' => 'expired'], 422);
        }

        Auth::login($user, $result['remember']);
        request()->session()->regenerate();
        REG::init();
        REG::setUserFromAuth();
        REG::persistOnboarding(clearSession: true, replaceSuccessUrl: true);
        REG::recordLoginArtifacts();

        $redirect = REG::getSuccessAuthUrl();
        $token = null;
        if ($result['source'] === 'mobile_token') {
            $context = REG::authSessionContext();
            if ($context !== null && $context !== '') {
                $token = $this->authSessions->issueMobileToken((int) $user->getAuthIdentifier(), $context);
            }
            $redirect = (string) config('user.auth_sessions.auth_success_url', $redirect);
        }

        $payload = [
            'success' => true,
            'redirect' => $redirect,
        ];
        if ($token !== null) {
            $payload['token'] = $token;
            $payload['token_type'] = 'Bearer';
        }

        return response()->json($payload);
    }
}

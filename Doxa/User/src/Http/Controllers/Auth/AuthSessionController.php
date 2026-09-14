<?php

namespace Doxa\User\Http\Controllers\Auth;

use Doxa\User\Libraries\AuthSessionService;
use Doxa\User\Libraries\Registration as REG;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Список и отзыв auth-сессий текущего пользователя.
 */
class AuthSessionController
{
    public function __construct(
        private readonly AuthSessionService $authSessions,
    ) {}

    /**
     * Возвращает список активных сессий текущего user.
     */
    public function index(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $list = $this->authSessions->listActiveSessions((int) Auth::id(), $this->sessionContext());

        return response()->json([
            'success' => true,
            'sessions' => $list,
        ]);
    }

    /**
     * Отзывает выбранную сессию (не текущую): revoked_at и удаление Laravel session для web_session.
     */
    public function destroy(int $id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $ok = $this->authSessions->revokeSessionRow((int) Auth::id(), $id, false, $this->sessionContext());
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => $ok,
        ], $ok ? 200 : 404);
    }

    /**
     * Возвращает product-контекст хоста для фильтра списка / отзыва.
     */
    private function sessionContext(): ?string
    {
        REG::init();
        $context = REG::authSessionContext();
        if ($context === null || $context === '') {
            return null;
        }

        return $context;
    }
}

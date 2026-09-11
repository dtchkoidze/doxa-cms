<?php

namespace Doxa\User\Http\Controllers\Auth;

use Doxa\User\Libraries\AuthSessionService;
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

        $list = $this->authSessions->listActiveSessions((int) Auth::id());

        return response()->json([
            'success' => true,
            'sessions' => $list,
        ]);
    }

    /**
     * Отзывает выбранную сессию (не текущую).
     */
    public function destroy(int $id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $ok = $this->authSessions->revokeSessionRow((int) Auth::id(), $id);
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
}

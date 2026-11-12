<?php

namespace Doxa\Mcp\Libraries;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Пользователь токена MCP: тот же провайдер и та же проверка активности, что у входа на сайт и mobile_token.
 */
class McpUsers
{
    /**
     * Возвращает активного пользователя по id; null — пользователя нет или он не активен.
     */
    public function findActive(int $userId): ?Authenticatable
    {
        $provider = Auth::createUserProvider(config('auth.guards.web.provider'));
        if ($provider === null) {
            throw new RuntimeException('MCP: user provider of web guard is not configured');
        }

        $user = $provider->retrieveById($userId);
        if (!$user instanceof Authenticatable || !$this->isActive($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Возвращает true, если пользователь активен: isActive() модели, если метод есть.
     */
    public function isActive(Authenticatable $user): bool
    {
        if (method_exists($user, 'isActive')) {
            return (bool) $user->isActive();
        }

        return true;
    }
}

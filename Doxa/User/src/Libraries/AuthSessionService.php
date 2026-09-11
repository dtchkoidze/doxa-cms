<?php

namespace Doxa\User\Libraries;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Учёт auth-сессий в user_auth_sessions: web_session и mobile_token.
 * Поле product — opaque-строка с хоста (аргумент $context); пакет не резолвит product сам.
 */
class AuthSessionService
{
    public const TABLE = 'user_auth_sessions';

    public const TYPE_WEB_SESSION = 'web_session';

    public const TYPE_MOBILE_TOKEN = 'mobile_token';

    public const REQUEST_SESSION_ID_KEY = 'user_auth_session_id';

    /**
     * Пишет активный web_session для текущего Laravel session id.
     * Без $context — не пишет (хост без product / legacy).
     */
    public function recordWebSessionAfterLogin(int $userId, ?string $context = null): void
    {
        if ($context === null || $context === '') {
            return;
        }

        $sessionId = request()->session()->getId();
        if ($sessionId === '') {
            throw new RuntimeException('user_auth_sessions: empty session id after login');
        }

        $existingId = DB::table(self::TABLE)
            ->where('session_id', $sessionId)
            ->where('type', self::TYPE_WEB_SESSION)
            ->whereNull('revoked_at')
            ->value('id');

        if ($existingId) {
            DB::table(self::TABLE)->where('id', $existingId)->update([
                'user_id' => $userId,
                'product' => $context,
                'label' => $this->requestLabel(),
                'last_used_at' => now(),
            ]);
            request()->attributes->set(self::REQUEST_SESSION_ID_KEY, (int) $existingId);

            return;
        }

        $id = DB::table(self::TABLE)->insertGetId([
            'user_id' => $userId,
            'product' => $context,
            'type' => self::TYPE_WEB_SESSION,
            'session_id' => $sessionId,
            'token_hash' => null,
            'label' => $this->requestLabel(),
            'created_at' => now(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ]);

        request()->attributes->set(self::REQUEST_SESSION_ID_KEY, (int) $id);
    }

    /**
     * Ставит revoked_at у web_session текущей Laravel session (до invalidate).
     */
    public function revokeCurrentWebSession(): void
    {
        if (!request()->hasSession()) {
            return;
        }

        $sessionId = request()->session()->getId();
        if ($sessionId === '') {
            return;
        }

        DB::table(self::TABLE)
            ->where('session_id', $sessionId)
            ->where('type', self::TYPE_WEB_SESSION)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Создаёт mobile_token. Возвращает plain token один раз.
     *
     * @throws RuntimeException если нет $context
     */
    public function issueMobileToken(int $userId, string $context): string
    {
        if ($context === '') {
            throw new RuntimeException('user_auth_sessions: context required for mobile token');
        }

        $plain = Str::random(64);
        $id = DB::table(self::TABLE)->insertGetId([
            'user_id' => $userId,
            'product' => $context,
            'type' => self::TYPE_MOBILE_TOKEN,
            'session_id' => null,
            'token_hash' => $this->hashToken($plain),
            'label' => $this->requestLabel(),
            'created_at' => now(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ]);

        request()->attributes->set(self::REQUEST_SESSION_ID_KEY, (int) $id);

        return $plain;
    }

    /**
     * Находит активный mobile_token по plain ключу.
     *
     * @return object{id: int|string, user_id: int|string}|null
     */
    public function findActiveMobileSession(string $plainToken): ?object
    {
        if ($plainToken === '') {
            return null;
        }

        $row = DB::table(self::TABLE)
            ->where('token_hash', $this->hashToken($plainToken))
            ->where('type', self::TYPE_MOBILE_TOKEN)
            ->whereNull('revoked_at')
            ->first();

        return $row ?: null;
    }

    /**
     * Возвращает id активного web_session по Laravel session id или null.
     */
    public function findActiveWebSessionId(int $userId, string $sessionId): ?int
    {
        if ($sessionId === '') {
            return null;
        }

        $id = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->where('type', self::TYPE_WEB_SESSION)
            ->whereNull('revoked_at')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Обновляет last_used_at и кладёт id сессии в request attributes.
     */
    public function markSessionUsed(int $sessionRowId): void
    {
        DB::table(self::TABLE)->where('id', $sessionRowId)->update([
            'last_used_at' => now(),
        ]);
        request()->attributes->set(self::REQUEST_SESSION_ID_KEY, $sessionRowId);
    }

    /**
     * Отзывает mobile_token текущего запроса (по plain из заголовка).
     */
    public function revokeMobileTokenFromRequest(): bool
    {
        $plain = $this->plainTokenFromRequest();
        if ($plain === null) {
            $sessionRowId = request()->attributes->get(self::REQUEST_SESSION_ID_KEY);
            if (!$sessionRowId) {
                return false;
            }

            return $this->revokeSessionRow((int) Auth::id(), (int) $sessionRowId, allowCurrent: true);
        }

        $session = $this->findActiveMobileSession($plain);
        if (!$session) {
            return false;
        }

        DB::table(self::TABLE)
            ->where('id', $session->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return true;
    }

    /**
     * Возвращает активные сессии user для Security UI.
     *
     * @return list<array{id: int, product: string, type: string, label: string|null, created_at: string|null, last_used_at: string|null, is_current: bool}>
     */
    public function listActiveSessions(int $userId): array
    {
        $currentId = $this->currentSessionIdForRequest($userId);

        $rows = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id' => (int) $row->id,
                'product' => (string) $row->product,
                'type' => (string) $row->type,
                'label' => $row->label !== null ? (string) $row->label : null,
                'created_at' => $row->created_at !== null ? (string) $row->created_at : null,
                'last_used_at' => $row->last_used_at !== null ? (string) $row->last_used_at : null,
                'is_current' => $currentId !== null && (int) $row->id === $currentId,
            ];
        }

        return $list;
    }

    /**
     * Отзывает чужую сессию user. Текущую — только если $allowCurrent.
     */
    public function revokeSessionRow(int $userId, int $sessionRowId, bool $allowCurrent = false): bool
    {
        $row = DB::table(self::TABLE)
            ->where('id', $sessionRowId)
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->first();

        if (!$row) {
            return false;
        }

        $currentId = $this->currentSessionIdForRequest($userId);
        if (!$allowCurrent && $currentId !== null && (int) $row->id === $currentId) {
            throw new RuntimeException('Cannot revoke the current session from the list');
        }

        DB::table(self::TABLE)
            ->where('id', $sessionRowId)
            ->update(['revoked_at' => now()]);

        return true;
    }

    /**
     * Читает plain token из Authorization Bearer или X-Api-Token.
     */
    public function plainTokenFromRequest(): ?string
    {
        $header = request()->header('Authorization', '');
        if (is_string($header) && preg_match('/^Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }

        $alt = request()->header('X-Api-Token');
        if (is_string($alt) && $alt !== '') {
            return $alt;
        }

        return null;
    }

    /**
     * @return int|null
     */
    private function currentSessionIdForRequest(int $userId): ?int
    {
        $attr = request()->attributes->get(self::REQUEST_SESSION_ID_KEY);
        if ($attr) {
            return (int) $attr;
        }

        if (request()->hasSession()) {
            $sessionId = request()->session()->getId();
            if ($sessionId !== '') {
                $id = DB::table(self::TABLE)
                    ->where('user_id', $userId)
                    ->where('session_id', $sessionId)
                    ->where('type', self::TYPE_WEB_SESSION)
                    ->whereNull('revoked_at')
                    ->value('id');
                if ($id) {
                    return (int) $id;
                }
            }
        }

        $plain = $this->plainTokenFromRequest();
        if ($plain !== null) {
            $session = $this->findActiveMobileSession($plain);
            if ($session && (int) $session->user_id === $userId) {
                return (int) $session->id;
            }
        }

        return null;
    }

    private function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    private function requestLabel(): string
    {
        $ua = (string) request()->userAgent();
        if (strlen($ua) > 512) {
            return substr($ua, 0, 512);
        }

        return $ua !== '' ? $ua : 'unknown';
    }
}

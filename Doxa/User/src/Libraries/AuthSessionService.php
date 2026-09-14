<?php

namespace Doxa\User\Libraries;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
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

    /** Долгоживущая cookie устройства (не auth). */
    public const DEVICE_COOKIE = 'auth_device';

    /** Срок cookie устройства, минуты (1 год). */
    private const DEVICE_COOKIE_MINUTES = 3 * 60 * 24 * 365;

    /**
     * Пишет активный web_session для текущего Laravel session id.
     * Один активный ряд на устройство (cookie auth_device) + product + type.
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

        $deviceId = $this->ensureDeviceId();
        $deviceFields = $this->requestDeviceFields();

        $existingId = DB::table(self::TABLE)
            ->where('session_id', $sessionId)
            ->where('type', self::TYPE_WEB_SESSION)
            ->whereNull('revoked_at')
            ->value('id');

        if ($existingId) {
            DB::table(self::TABLE)->where('id', $existingId)->update(array_merge($deviceFields, [
                'user_id' => $userId,
                'product' => $context,
                'device_id' => $deviceId,
                'last_used_at' => now(),
            ]));
            request()->attributes->set(self::REQUEST_SESSION_ID_KEY, (int) $existingId);

            return;
        }

        $deviceRowId = $this->findActiveDeviceSessionId($userId, $context, self::TYPE_WEB_SESSION, $deviceId);
        if ($deviceRowId) {
            $oldSessionId = DB::table(self::TABLE)->where('id', $deviceRowId)->value('session_id');
            DB::table(self::TABLE)->where('id', $deviceRowId)->update(array_merge($deviceFields, [
                'session_id' => $sessionId,
                'last_used_at' => now(),
            ]));
            if (is_string($oldSessionId) && $oldSessionId !== '' && $oldSessionId !== $sessionId) {
                $this->destroyLaravelSession($oldSessionId);
            }
            request()->attributes->set(self::REQUEST_SESSION_ID_KEY, $deviceRowId);

            return;
        }

        $id = DB::table(self::TABLE)->insertGetId(array_merge($deviceFields, [
            'user_id' => $userId,
            'product' => $context,
            'type' => self::TYPE_WEB_SESSION,
            'session_id' => $sessionId,
            'token_hash' => null,
            'device_id' => $deviceId,
            'created_at' => now(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ]));

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
     * Выдаёт mobile_token. Один активный токен на устройство (cookie auth_device) + product.
     * Возвращает plain token один раз.
     *
     * @throws RuntimeException если нет $context
     */
    public function issueMobileToken(int $userId, string $context): string
    {
        if ($context === '') {
            throw new RuntimeException('user_auth_sessions: context required for mobile token');
        }

        $plain = Str::random(64);
        $deviceId = $this->ensureDeviceId();
        $deviceFields = $this->requestDeviceFields();
        $hash = $this->hashToken($plain);

        $deviceRowId = $this->findActiveDeviceSessionId($userId, $context, self::TYPE_MOBILE_TOKEN, $deviceId);
        if ($deviceRowId) {
            DB::table(self::TABLE)->where('id', $deviceRowId)->update(array_merge($deviceFields, [
                'token_hash' => $hash,
                'last_used_at' => now(),
            ]));
            request()->attributes->set(self::REQUEST_SESSION_ID_KEY, $deviceRowId);

            return $plain;
        }

        $id = DB::table(self::TABLE)->insertGetId(array_merge($deviceFields, [
            'user_id' => $userId,
            'product' => $context,
            'type' => self::TYPE_MOBILE_TOKEN,
            'session_id' => null,
            'token_hash' => $hash,
            'device_id' => $deviceId,
            'created_at' => now(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ]));

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
     * Помечает web_session использованной: last_used_at и актуальный Laravel session id.
     *
     * @param object{id: int|string, session_id: string|null} $row
     */
    public function markWebSessionUsed(object $row): void
    {
        $update = ['last_used_at' => now()];

        if (request()->hasSession()) {
            $sessionId = request()->session()->getId();
            if ($sessionId !== '' && (string) $row->session_id !== $sessionId) {
                $oldSessionId = $row->session_id !== null ? (string) $row->session_id : '';
                $update['session_id'] = $sessionId;
                if ($oldSessionId !== '') {
                    $this->destroyLaravelSession($oldSessionId);
                }
            }
        }

        DB::table(self::TABLE)->where('id', $row->id)->update($update);
        request()->attributes->set(self::REQUEST_SESSION_ID_KEY, (int) $row->id);
    }

    /**
     * Возвращает web_session текущего запроса, в том числе отозванный ряд.
     * Сначала по Laravel session id, иначе по cookie устройства (без создания cookie).
     *
     * @return object{id: int|string, session_id: string|null, revoked_at: mixed}|null
     */
    public function findWebSessionForRequest(int $userId): ?object
    {
        if (request()->hasSession()) {
            $sessionId = request()->session()->getId();
            if ($sessionId !== '') {
                $row = DB::table(self::TABLE)
                    ->where('user_id', $userId)
                    ->where('session_id', $sessionId)
                    ->where('type', self::TYPE_WEB_SESSION)
                    ->first();
                if ($row) {
                    return $row;
                }
            }
        }

        $deviceId = $this->existingDeviceId();
        if ($deviceId === null) {
            return null;
        }

        $row = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('type', self::TYPE_WEB_SESSION)
            ->where('device_id', $deviceId)
            ->orderByRaw('revoked_at IS NULL DESC')
            ->orderByDesc('id')
            ->first();

        return $row ?: null;
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
     * $context — фильтр по product (код продукта хоста); null / '' — все продукты.
     *
     * @return list<array{id: int, product: string, type: string, label: string|null, country: string|null, created_at: string|null, last_used_at: string|null, is_current: bool}>
     */
    public function listActiveSessions(int $userId, ?string $context = null): array
    {
        $currentId = $this->currentSessionIdForRequest($userId);

        $query = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNull('revoked_at');

        if ($context !== null && $context !== '') {
            $query->where('product', $context);
        }

        $rows = $query
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get();

        $countryByDevice = (new UserGeo())->latestCountryByDeviceId($userId);

        $list = [];
        foreach ($rows as $row) {
            $deviceId = $row->device_id !== null ? (string) $row->device_id : '';
            $list[] = [
                'id' => (int) $row->id,
                'product' => (string) $row->product,
                'type' => (string) $row->type,
                'label' => $row->label !== null ? (string) $row->label : null,
                'country' => ($deviceId !== '' && isset($countryByDevice[$deviceId])) ? $countryByDevice[$deviceId] : null,
                'created_at' => $row->created_at !== null ? (string) $row->created_at : null,
                'last_used_at' => $row->last_used_at !== null ? (string) $row->last_used_at : null,
                'is_current' => $currentId !== null && (int) $row->id === $currentId,
            ];
        }

        return $list;
    }

    /**
     * Отзывает чужую сессию user. Текущую — только если $allowCurrent.
     * Для web_session удаляет Laravel session по session_id.
     * $context — только сессии этого product; null / '' — без фильтра.
     */
    public function revokeSessionRow(int $userId, int $sessionRowId, bool $allowCurrent = false, ?string $context = null): bool
    {
        $query = DB::table(self::TABLE)
            ->where('id', $sessionRowId)
            ->where('user_id', $userId)
            ->whereNull('revoked_at');

        if ($context !== null && $context !== '') {
            $query->where('product', $context);
        }

        $row = $query->first();

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

        if ((string) $row->type === self::TYPE_WEB_SESSION && $row->session_id !== null && (string) $row->session_id !== '') {
            $this->destroyLaravelSession((string) $row->session_id);
        }

        return true;
    }

    /**
     * Отзывает все активные сессии user и удаляет связанные Laravel-сессии.
     */
    public function revokeAllSessionsForUser(int $userId): void
    {
        $rows = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->get(['id', 'type', 'session_id']);

        if ($rows->isEmpty()) {
            return;
        }

        DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $currentLaravelSessionId = request()->hasSession() ? request()->session()->getId() : '';

        foreach ($rows as $row) {
            if ((string) $row->type !== self::TYPE_WEB_SESSION) {
                continue;
            }
            $sessionId = $row->session_id !== null ? (string) $row->session_id : '';
            if ($sessionId === '' || $sessionId === $currentLaravelSessionId) {
                continue;
            }
            $this->destroyLaravelSession($sessionId);
        }
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

    /**
     * Возвращает uuid устройства текущего запроса (cookie auth_device).
     */
    public function deviceId(): string
    {
        return $this->ensureDeviceId();
    }

    /**
     * Label текущего запроса для записи сессии.
     *
     * @return array{label: string}
     */
    protected function requestDeviceFields(): array
    {
        return [
            'label' => $this->requestLabel(),
        ];
    }

    /**
     * Человекочитаемая подпись устройства (хост может переопределить).
     */
    protected function requestLabel(): string
    {
        $ua = (string) request()->userAgent();
        if (strlen($ua) > 512) {
            return substr($ua, 0, 512);
        }

        return $ua !== '' ? $ua : 'unknown';
    }

    /**
     * Возвращает uuid устройства из cookie или создаёт новый и кладёт в cookie.
     */
    private function ensureDeviceId(): string
    {
        $existing = $this->existingDeviceId();
        if ($existing !== null) {
            $this->queueDeviceCookie($existing);

            return $existing;
        }

        $deviceId = strtolower((string) Str::uuid());
        $this->queueDeviceCookie($deviceId);
        request()->cookies->set(self::DEVICE_COOKIE, $deviceId);

        return $deviceId;
    }

    /**
     * Возвращает uuid устройства из cookie или null; cookie не создаёт.
     */
    private function existingDeviceId(): ?string
    {
        $existing = request()->cookie(self::DEVICE_COOKIE);
        if (is_string($existing) && Str::isUuid($existing)) {
            return strtolower($existing);
        }

        return null;
    }

    /**
     * Кладёт cookie устройства (не auth).
     */
    private function queueDeviceCookie(string $deviceId): void
    {
        Cookie::queue(self::DEVICE_COOKIE, $deviceId, self::DEVICE_COOKIE_MINUTES);
    }

    /**
     * Возвращает id активной сессии этого устройства или null.
     */
    private function findActiveDeviceSessionId(int $userId, string $context, string $type, string $deviceId): ?int
    {
        $id = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('product', $context)
            ->where('type', $type)
            ->where('device_id', $deviceId)
            ->whereNull('revoked_at')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Удаляет Laravel session по id (отзыв из списка или замена session_id на устройстве).
     */
    private function destroyLaravelSession(string $sessionId): void
    {
        request()->session()->getHandler()->destroy($sessionId);
    }
}

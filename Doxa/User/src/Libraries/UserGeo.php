<?php

namespace Doxa\User\Libraries;

use Illuminate\Support\Facades\DB;

/**
 * Журнал входов в user_logins: IP, UA, страна (CF-IPCountry), устройство.
 */
class UserGeo
{
    public const TABLE = 'user_logins';

    /**
     * Пишет строку входа. IP/UA/страна — из текущего запроса, если не переданы.
     *
     * @param int $userId
     * @param string|null $ip
     * @param string|null $userAgent
     * @param string|null $deviceId uuid cookie auth_device
     * @return void
     */
    public function record(int $userId, ?string $ip = null, ?string $userAgent = null, ?string $deviceId = null): void
    {
        if ($ip === null) {
            $ip = request()->header('CF-Connecting-IP') ?? request()->ip();
        }
        $userAgent = $userAgent ?? request()->userAgent();

        DB::table(self::TABLE)->insert([
            'user_id' => $userId,
            'ip' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent ? substr($userAgent, 0, 512) : null,
            'country' => $this->requestCountry(),
            'device_id' => $deviceId,
            'logged_at' => now(),
        ]);
    }

    /**
     * Возвращает историю входов пользователя (новые сверху).
     *
     * @param int $userId
     * @param int|null $limit
     * @return \Illuminate\Support\Collection
     */
    public function getByUserId(int $userId, ?int $limit = null)
    {
        $query = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->orderByDesc('logged_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Возвращает последние входы по всем пользователям (новые сверху).
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRecent(int $limit = 50)
    {
        return DB::table(self::TABLE)
            ->orderByDesc('logged_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Возвращает ISO2 страны последнего входа по каждому device_id пользователя.
     *
     * @return array<string, string>
     */
    public function latestCountryByDeviceId(int $userId): array
    {
        $rows = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNotNull('device_id')
            ->where('device_id', '!=', '')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->orderByDesc('logged_at')
            ->get(['device_id', 'country']);

        $map = [];
        foreach ($rows as $row) {
            $deviceId = (string) $row->device_id;
            if ($deviceId === '' || isset($map[$deviceId])) {
                continue;
            }
            $map[$deviceId] = (string) $row->country;
        }

        return $map;
    }

    /**
     * Возвращает ISO2 из CF-IPCountry или null, если заголовка нет / служебный код.
     */
    private function requestCountry(): ?string
    {
        $header = request()->header('CF-IPCountry');
        if (!is_string($header)) {
            return null;
        }

        $code = strtolower(trim($header));
        if (!preg_match('/^[a-z]{2}$/', $code) || $code === 'xx' || $code === 't1') {
            return null;
        }

        return $code;
    }
}

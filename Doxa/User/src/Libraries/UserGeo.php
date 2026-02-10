<?php

namespace Doxa\User\Libraries;

use Illuminate\Support\Facades\DB;

/**
 * Records user events with IP/User-Agent (e.g. logins) and provides methods for analysis.
 */
class UserGeo
{
    public const TABLE = 'user_logins';

    /**
     * Record a login event. Uses current request IP/UA if not provided.
     *
     * @param int $userId
     * @param string|null $ip
     * @param string|null $userAgent
     * @return void
     */
    public function record(int $userId, ?string $ip = null, ?string $userAgent = null): void
    {
        if ($ip === null) {
            // Cloudflare provides real user IP in CF-Connecting-IP header
            $ip = request()->header('CF-Connecting-IP') ?? request()->ip();
        }
        $userAgent = $userAgent ?? request()->userAgent();

        DB::table(self::TABLE)->insert([
            'user_id' => $userId,
            'ip' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent ? substr($userAgent, 0, 512) : null,
            'logged_at' => now(),
        ]);
    }

    /**
     * Get login history for a user (newest first).
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
     * Get recent logins across all users (newest first).
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
}

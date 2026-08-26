<?php

namespace Doxa\User\Libraries;

use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class Onboarding
{
    public const TARGET_SUCCESS_URL = 'success_url';

    public const SESSION_KEY = 'onboarding_query';

    public function __construct(protected int $userId)
    {
        if ($this->userId <= 0) {
            throw new RuntimeException('Onboarding user id is required');
        }
    }

    public static function enabled(): bool
    {
        $map = config('onboarding.onboarding_query_keys');

        return is_array($map) && $map !== [];
    }

    public static function make(int $userId): self
    {
        $class = config('onboarding.handler');
        if (!is_string($class) || $class === '') {
            $class = self::class;
        }

        return new $class($userId);
    }

    /**
     * @return array<string, array{target: string, type?: string}>
     */
    public static function queryKeys(): array
    {
        $map = config('onboarding.onboarding_query_keys');
        if (!is_array($map) || $map === []) {
            throw new RuntimeException('config onboarding.onboarding_query_keys is missing');
        }

        foreach ($map as $key => $spec) {
            if (!is_string($key) || $key === '') {
                throw new RuntimeException('onboarding_query_keys key must be a non-empty string');
            }
            if (!is_array($spec) || !isset($spec['target'])) {
                throw new RuntimeException('onboarding_query_keys.' . $key . ' must have target');
            }
            if (!is_string($spec['target']) || $spec['target'] === '') {
                throw new RuntimeException('onboarding_query_keys.' . $key . ' target must be a non-empty string');
            }
            if (array_key_exists('type', $spec) && (!is_string($spec['type']) || $spec['type'] === '')) {
                throw new RuntimeException('onboarding_query_keys.' . $key . ' type must be a non-empty string when set');
            }
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    public static function requestValues(): array
    {
        if (!self::enabled()) {
            return [];
        }

        $out = [];
        foreach (array_keys(self::queryKeys()) as $key) {
            $value = request($key);
            if ($value === null || $value === '') {
                continue;
            }
            if (!is_string($value)) {
                throw new RuntimeException('Onboarding query ' . $key . ' must be a string');
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Query лендинга с Referer: кнопка соцлогина открывает redirect без search.
     *
     * @return array<string, string>
     */
    public static function refererValues(): array
    {
        if (!self::enabled()) {
            return [];
        }

        $referer = request()->header('referer');
        if (!is_string($referer) || $referer === '') {
            return [];
        }

        $parts = parse_url($referer);
        if (!is_array($parts) || !isset($parts['host']) || $parts['host'] !== request()->getHost()) {
            return [];
        }

        if (!isset($parts['query']) || $parts['query'] === '') {
            return [];
        }

        $query = [];
        parse_str($parts['query'], $query);

        $out = [];
        foreach (array_keys(self::queryKeys()) as $key) {
            if (!array_key_exists($key, $query) || $query[$key] === '' || $query[$key] === null) {
                continue;
            }
            if (!is_string($query[$key])) {
                throw new RuntimeException('Onboarding query ' . $key . ' must be a string');
            }
            $out[$key] = $query[$key];
        }

        return $out;
    }

    public static function saveRequestToSession(): void
    {
        if (!self::enabled()) {
            return;
        }

        $values = self::requestValues();
        $source = 'request';
        if ($values === []) {
            $values = self::refererValues();
            $source = 'referer';
        }
        if ($values === []) {
            return;
        }

        Clog::write(Registration::LOG, 'Onboarding saveRequestToSession', [
            'source' => $source,
            'values' => $values,
        ], Clog::NOTICE);

        self::saveToSession($values);
    }

    /**
     * @param array<string, string> $values
     */
    public static function saveToSession(array $values): void
    {
        if (!self::enabled() || $values === []) {
            return;
        }

        $current = session(self::SESSION_KEY);
        if (!is_array($current)) {
            $current = [];
        }

        $merged = array_merge($current, $values);
        session([self::SESSION_KEY => $merged]);

        Clog::write(Registration::LOG, 'Onboarding saveToSession', [
            'was' => $current,
            'add' => $values,
            'session' => $merged,
        ], Clog::NOTICE);
    }

    public static function clearSession(): void
    {
        $was = session(self::SESSION_KEY);
        session()->forget(self::SESSION_KEY);
        Clog::write(Registration::LOG, 'Onboarding clearSession', [
            'was' => is_array($was) ? $was : $was,
        ], Clog::NOTICE);
    }

    public function saveToUser(bool $clearSession = false, bool $replaceSuccessUrl = false, bool $useSession = true): void
    {
        if (!self::enabled()) {
            Clog::write(Registration::LOG, 'Onboarding saveToUser skipped (disabled)', [
                'user_id' => $this->userId,
            ], Clog::NOTICE);
            return;
        }

        $fromSession = [];
        if ($useSession) {
            $fromSession = session(self::SESSION_KEY);
            if (!is_array($fromSession)) {
                $fromSession = [];
            }
        }

        $fromRequest = self::requestValues();
        $values = array_merge($fromSession, $fromRequest);

        Clog::write(Registration::LOG, 'Onboarding saveToUser', [
            'user_id' => $this->userId,
            'clear_session' => $clearSession,
            'replace_success_url' => $replaceSuccessUrl,
            'use_session' => $useSession,
            'session' => $fromSession,
            'request' => $fromRequest,
            'merged' => $values,
        ], Clog::NOTICE);

        $map = self::queryKeys();
        foreach ($values as $queryKey => $raw) {
            if (!isset($map[$queryKey])) {
                Clog::write(Registration::LOG, 'Onboarding saveToUser skip unknown key', [
                    'user_id' => $this->userId,
                    'key' => $queryKey,
                ], Clog::NOTICE);
                continue;
            }
            if (!is_string($raw)) {
                throw new RuntimeException('Onboarding query ' . $queryKey . ' must be a string');
            }
            $value = $this->normalizeValue($queryKey, $raw);
            if ($value === null) {
                Clog::write(Registration::LOG, 'Onboarding saveToUser skip after normalize', [
                    'user_id' => $this->userId,
                    'key' => $queryKey,
                    'raw' => $raw,
                ], Clog::NOTICE);
                continue;
            }
            $this->upsert($map[$queryKey], $value);
        }

        if ($replaceSuccessUrl && self::hasSuccessUrlQueryKey()) {
            $successKey = self::successQueryKey();
            if ($successKey !== null && !array_key_exists($successKey, $values)) {
                Clog::write(Registration::LOG, 'Onboarding saveToUser replaceSuccessUrl: key absent, clear row', [
                    'user_id' => $this->userId,
                    'success_key' => $successKey,
                ], Clog::NOTICE);
                $this->clearByTarget(self::TARGET_SUCCESS_URL);
            }
        }

        if ($clearSession) {
            self::clearSession();
        }
    }

    public function successUrl(): ?string
    {
        $key = self::successQueryKey();
        if ($key === null) {
            Clog::write(Registration::LOG, 'Onboarding successUrl: no success_url in config', [
                'user_id' => $this->userId,
            ], Clog::NOTICE);
            return null;
        }

        $url = $this->valueByQueryKey($key);
        Clog::write(Registration::LOG, 'Onboarding successUrl', [
            'user_id' => $this->userId,
            'query_key' => $key,
            'url' => $url,
        ], Clog::NOTICE);

        return $url;
    }

    public function clearByTarget(string $target): void
    {
        $existing = DB::table('onboarding')
            ->where('user_id', $this->userId)
            ->where('target', $target)
            ->first();

        DB::table('onboarding')
            ->where('user_id', $this->userId)
            ->where('target', $target)
            ->delete();

        Clog::write(Registration::LOG, 'Onboarding clearByTarget', [
            'user_id' => $this->userId,
            'target' => $target,
            'had_row' => $existing ? (string) $existing->value : null,
        ], Clog::NOTICE);
    }

    public static function successQueryKey(): ?string
    {
        if (!self::enabled()) {
            return null;
        }

        foreach (self::queryKeys() as $key => $spec) {
            if ($spec['target'] === self::TARGET_SUCCESS_URL) {
                return $key;
            }
        }

        return null;
    }

    /**
     * В конфиге onboarding_query_keys есть ключ с target success_url.
     * Не проверяет строку в таблице у пользователя.
     */
    public static function hasSuccessUrlQueryKey(): bool
    {
        return self::successQueryKey() !== null;
    }

    public function valueByQueryKey(string $queryKey): ?string
    {
        $map = self::queryKeys();
        if (!isset($map[$queryKey])) {
            throw new RuntimeException('Unknown onboarding query key: ' . $queryKey);
        }

        return $this->value($map[$queryKey]);
    }

    /**
     * @param array{target: string, type?: string} $spec
     */
    public function value(array $spec): ?string
    {
        $row = $this->findRow($spec);
        if (!$row) {
            Clog::write(Registration::LOG, 'Onboarding read: no row', [
                'user_id' => $this->userId,
                'target' => $spec['target'],
            ], Clog::NOTICE);
            return null;
        }

        $value = (string) $row->value;
        if ($value === '') {
            throw new RuntimeException('Onboarding ' . $spec['target'] . ' has empty value');
        }

        Clog::write(Registration::LOG, 'Onboarding read', [
            'user_id' => $this->userId,
            'target' => $spec['target'],
            'value' => $value,
        ], Clog::NOTICE);

        return $value;
    }

    /**
     * @return array<string, string>
     */
    protected function valuesByTarget(): array
    {
        $rows = DB::table('onboarding')
            ->where('user_id', $this->userId)
            ->orderBy('id')
            ->get(['target', 'value']);

        $out = [];
        foreach ($rows as $row) {
            $value = (string) $row->value;
            if ($value === '') {
                throw new RuntimeException('Onboarding ' . $row->target . ' has empty value');
            }
            $out[$row->target] = $value;
        }

        Clog::write(Registration::LOG, 'Onboarding valuesByTarget', [
            'user_id' => $this->userId,
            'rows' => $out,
        ], Clog::NOTICE);

        return $out;
    }

    /**
     * @param array{target: string, type?: string} $spec
     */
    protected function upsert(array $spec, string $value): void
    {
        $existing = $this->findRow($spec);
        $type = $this->specType($spec);

        if ($existing) {
            DB::table('onboarding')
                ->where('id', $existing->id)
                ->update(['value' => $value]);

            $log = [
                'user_id' => $this->userId,
                'target' => $spec['target'],
                'value' => $value,
            ];
            if ($type !== null) {
                $log['type'] = $type;
            }
            Clog::write(Registration::LOG, 'Onboarding update', $log, Clog::NOTICE);

            return;
        }

        $row = [
            'user_id' => $this->userId,
            'target' => $spec['target'],
            'value' => $value,
            'created_at' => now(),
        ];
        if ($type !== null) {
            $row['type'] = $type;
        }

        DB::table('onboarding')->insert($row);

        $log = [
            'user_id' => $this->userId,
            'target' => $spec['target'],
            'value' => $value,
        ];
        if ($type !== null) {
            $log['type'] = $type;
        }
        Clog::write(Registration::LOG, 'Onboarding insert', $log, Clog::NOTICE);
    }

    /**
     * @param array{target: string, type?: string} $spec
     */
    protected function findRow(array $spec): ?object
    {
        $query = DB::table('onboarding')
            ->where('user_id', $this->userId)
            ->where('target', $spec['target']);

        $type = $this->specType($spec);
        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->first();
    }

    /**
     * @param array{target: string, type?: string} $spec
     */
    protected function specType(array $spec): ?string
    {
        if (!array_key_exists('type', $spec)) {
            return null;
        }

        return $spec['type'];
    }

    protected function normalizeValue(string $queryKey, string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            throw new RuntimeException('Onboarding query ' . $queryKey . ' is empty');
        }

        if ($queryKey === self::successQueryKey()) {
            return $this->normalizeSuccessUrl($trimmed);
        }

        return $trimmed;
    }

    protected function normalizeSuccessUrl(string $referer): ?string
    {
        $safe = $this->sanitizePath($referer);
        if ($safe === null) {
            throw new RuntimeException('Onboarding referer is not a safe path: ' . $referer);
        }

        return $safe;
    }

    protected function sanitizePath(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('/[\x00-\x1f\\\\]/', $url)) {
            return null;
        }

        $decoded = rawurldecode($url);
        if (preg_match('#^(javascript|data|vbscript):#i', $decoded)) {
            return null;
        }

        if (str_starts_with($url, '//') || str_starts_with($decoded, '//')) {
            return null;
        }

        if (preg_match('#^https?://#i', $url) || preg_match('#^https?://#i', $decoded)) {
            $parsed = parse_url($url);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: request()->getHost();
            if (empty($parsed['host']) || strcasecmp($parsed['host'], (string) $appHost) !== 0) {
                return null;
            }
            $path = $parsed['path'] ?? '/';
            if (isset($parsed['query'])) {
                $path .= '?' . $parsed['query'];
            }
            $url = $path;
        }

        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        if (Str::contains($url, '/auth/')) {
            return null;
        }

        return $url;
    }
}

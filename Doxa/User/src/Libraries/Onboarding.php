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
        Clog::write(Registration::LOG, 'ONBOARDING::make(userId: ' . $userId . ')', Clog::DEBUG);
        $class = config('onboarding.handler');
        if (!is_string($class) || $class === '') {
            $class = self::class;
        }
        Clog::write(Registration::LOG, 'ONBOARDING::class: ' . $class, Clog::DEBUG);

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
     * Query-параметры с Referer той же зоны: кнопка соцлогина открывает redirect без search.
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

        Clog::write(
            Registration::LOG,
            'Onboarding: кладу в сессию ' . self::SESSION_KEY . ' query-ключи из конфига onboarding_query_keys '
            . '(потом при логине уйдут в таблицу onboarding).' . "\n"
            . 'Источник: ' . ($source === 'request'
                ? 'query текущего запроса'
                : 'query из Referer той же зоны (кнопка соцлогина уходит без search)') . ".\n"
            . 'Ключи конфига: ' . self::formatConfiguredKeys() . ".\n"
            . 'Значения сейчас: ' . self::formatPairs($values) . '.',
            Clog::NOTICE
        );

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

        Clog::write(
            Registration::LOG,
            'Onboarding: обновляю сессию ' . self::SESSION_KEY . ' (временное хранилище до записи в БД).' . "\n"
            . 'Было в сессии: ' . self::formatPairs($current) . ".\n"
            . 'Добавляю/перезаписываю: ' . self::formatPairs($values) . ".\n"
            . 'Стало в сессии: ' . self::formatPairs($merged) . '.',
            Clog::NOTICE
        );
    }

    public static function clearSession(): void
    {
        $was = session(self::SESSION_KEY);
        session()->forget(self::SESSION_KEY);
        $wasPairs = is_array($was) ? self::formatPairs($was) : '(сессии не было)';
        Clog::write(
            Registration::LOG,
            'Onboarding: очищаю сессию ' . self::SESSION_KEY
            . ' — параметры уже перенесены в БД или больше не нужны.' . "\n"
            . 'Что удалили: ' . $wasPairs . '.',
            Clog::NOTICE
        );
    }

    public function saveToUser(bool $clearSession = false, bool $replaceSuccessUrl = false, bool $useSession = true): void
    {
        if (!self::enabled()) {
            Clog::write(
                Registration::LOG,
                'Onboarding: запись в БД пропущена — onboarding выключен (пустой onboarding_query_keys).'
                . ' user_id=' . $this->userId . '.',
                Clog::NOTICE
            );
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
        if ($fromRequest !== []) {
            Clog::write(Registration::LOG, 'ONBOARDING values from request: ', $fromRequest, Clog::DEBUG);
        }
        if ($fromSession !== []) {
            Clog::write(Registration::LOG, 'ONBOARDING values from session: ', $fromSession, Clog::DEBUG);
        }
        $values = array_merge($fromSession, $fromRequest);
        if ($values !== []) {
            Clog::write(Registration::LOG, 'ONBOARDING merged values: ', $values, Clog::DEBUG);
        } else {
            Clog::write(Registration::LOG, 'ONBOARDING values is empty', Clog::DEBUG);
        }

        $successUrlKey = self::successUrlQueryKey();
        Clog::write(Registration::LOG, 'ONBOARDING::successUrlKey: ' . $successUrlKey, Clog::DEBUG);

        if ($values === []) {
            Clog::write(
                Registration::LOG,
                'Onboarding: после логина для user_id=' . $this->userId
                . ' — в сессии ' . self::SESSION_KEY . ' и в query запроса нет ни одного ключа из конфига '
                . '(' . self::formatConfiguredKeys() . ').' . "\n"
                . 'Новые строки в таблицу onboarding не пишутся.' . "\n"
                . 'Читать сессию: ' . ($useSession ? 'да' : 'нет') . '.' . "\n"
                . 'Очистить сессию всё равно: ' . ($clearSession ? 'да' : 'нет') . '.' . "\n"
                . 'Сбросить старый success_url в БД (ключа «' . (string) $successUrlKey . '» нет): '
                . ($replaceSuccessUrl ? 'да' : 'нет') . '.',
                Clog::NOTICE
            );
        } else {
            Clog::write(
                Registration::LOG,
                'Onboarding: пишу в таблицу onboarding для user_id=' . $this->userId . '.' . "\n"
                . 'Ключи конфига (query → target): ' . self::formatConfiguredKeys() . '.' . "\n"
                . 'Читать сессию ' . self::SESSION_KEY . ': ' . ($useSession ? 'да' : 'нет (только query запроса)') . ".\n"
                . 'В сессии: ' . self::formatPairs($fromSession) . ".\n"
                . 'В query запроса: ' . self::formatPairs($fromRequest) . ".\n"
                . 'Итого запишу (запрос перекрывает сессию): ' . self::formatPairs($values) . ".\n"
                . 'После записи очистить сессию: ' . ($clearSession ? 'да' : 'нет') . ".\n"
                . 'Если нет ключа «' . (string) $successUrlKey . '» — сбросить старый success_url в БД: '
                . ($replaceSuccessUrl ? 'да' : 'нет') . '.',
                Clog::NOTICE
            );
        }

        $map = self::queryKeys();
        foreach ($values as $queryKey => $raw) {
            if (!isset($map[$queryKey])) {
                Clog::write(
                    Registration::LOG,
                    'Onboarding: пропускаю неизвестный ключ «' . $queryKey . '» для user_id=' . $this->userId
                    . ' — его нет в onboarding_query_keys, в БД не пишем.',
                    Clog::NOTICE
                );
                continue;
            }
            if (!is_string($raw)) {
                throw new RuntimeException('Onboarding query ' . $queryKey . ' must be a string');
            }
            $value = $this->normalizeValue($queryKey, $raw);
            if ($value === null) {
                Clog::write(
                    Registration::LOG,
                    'Onboarding: ключ «' . $queryKey . '» для user_id=' . $this->userId
                    . ' отброшен после нормализации (сырое значение «' . $raw . '» не подходит для записи).',
                    Clog::NOTICE
                );
                continue;
            }
            $this->upsert($map[$queryKey], $value);
        }

        if ($replaceSuccessUrl && self::hasSuccessUrlQueryKey()) {
            if ($successUrlKey !== null && !array_key_exists($successUrlKey, $values)) {
                Clog::write(
                    Registration::LOG,
                    'Onboarding: в данных для записи нет query-ключа «' . $successUrlKey . '» '
                    . '(это referer → target success_url), а режим replace включён.' . "\n"
                    . 'Поэтому удаляю у user_id=' . $this->userId . ' строку onboarding.target=success_url, '
                    . 'чтобы после логина не увести на старый URL из прошлой сессии.',
                    Clog::NOTICE
                );
                $this->clearByTarget(self::TARGET_SUCCESS_URL);
            }
        }

        if ($clearSession) {
            self::clearSession();
        }
    }

    public function successUrl(): ?string
    {
        $successUrlKey = self::successUrlQueryKey();
        if ($successUrlKey === null) {
            Clog::write(
                Registration::LOG,
                'Onboarding: не могу прочитать URL после логина — в конфиге нет ключа с target=success_url.'
                . ' user_id=' . $this->userId . '.',
                Clog::NOTICE
            );
            return null;
        }

        $url = $this->valueByQueryKey($successUrlKey);
        Clog::write(
            Registration::LOG,
            'Onboarding: читаю URL редиректа после логина для user_id=' . $this->userId . '.' . "\n"
            . 'Query-ключ конфига: «' . $successUrlKey . '» → строка onboarding.target=success_url.' . "\n"
            . 'Значение: ' . ($url !== null && $url !== '' ? $url : '(нет строки в БД)') . '.',
            Clog::NOTICE
        );

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

        Clog::write(
            Registration::LOG,
            'Onboarding: удаляю из таблицы onboarding строку target=«' . $target . '» '
            . 'у user_id=' . $this->userId . '.' . "\n"
            . ($existing
                ? 'До удаления там было: «' . (string) $existing->value . '».'
                : 'Строки и так не было — удалять было нечего.'),
            Clog::NOTICE
        );
    }
    public static function successUrlQueryKey(): ?string
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
        return self::successUrlQueryKey() !== null;
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
            Clog::write(
                Registration::LOG,
                'Onboarding: в таблице onboarding нет строки target=«' . $spec['target'] . '» '
                . 'для user_id=' . $this->userId . ' — значение отсутствует.',
                Clog::NOTICE
            );
            return null;
        }

        $value = (string) $row->value;
        if ($value === '') {
            throw new RuntimeException('Onboarding ' . $spec['target'] . ' has empty value');
        }

        Clog::write(
            Registration::LOG,
            'Onboarding: прочитал из БД target=«' . $spec['target'] . '» для user_id=' . $this->userId
            . ': «' . $value . '».',
            Clog::NOTICE
        );

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

        Clog::write(
            Registration::LOG,
            'Onboarding: все строки onboarding для user_id=' . $this->userId . ': '
            . self::formatPairs($out) . '.',
            Clog::NOTICE
        );

        return $out;
    }

    /**
     * @param array{target: string, type?: string} $spec
     */
    protected function upsert(array $spec, string $value): void
    {
        $existing = $this->findRow($spec);
        $type = $this->specType($spec);
        $typeSuffix = $type !== null ? ', type=«' . $type . '»' : '';

        if ($existing) {
            DB::table('onboarding')
                ->where('id', $existing->id)
                ->update(['value' => $value]);

            Clog::write(
                Registration::LOG,
                'Onboarding: обновил в БД target=«' . $spec['target'] . '»' . $typeSuffix
                . ' для user_id=' . $this->userId . '.' . "\n"
                . 'Было: «' . (string) $existing->value . '» → стало: «' . $value . '».',
                Clog::NOTICE
            );

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

        Clog::write(
            Registration::LOG,
            'Onboarding: создал в БД строку target=«' . $spec['target'] . '»' . $typeSuffix
            . ' для user_id=' . $this->userId . ' со значением «' . $value . '».',
            Clog::NOTICE
        );
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

        if ($queryKey === self::successUrlQueryKey()) {
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

    /**
     * Пары ключ=значение одной строкой для лога.
     *
     * @param array<string, mixed> $pairs
     */
    protected static function formatPairs(array $pairs): string
    {
        if ($pairs === []) {
            return '(пусто)';
        }

        $parts = [];
        foreach ($pairs as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $parts[] = $key . '=' . (string) $value;
        }

        return implode(', ', $parts);
    }

    /**
     * Карта query-ключ → target из конфига, для лога.
     */
    protected static function formatConfiguredKeys(): string
    {
        if (!self::enabled()) {
            return '(onboarding выключен)';
        }

        $parts = [];
        foreach (self::queryKeys() as $queryKey => $spec) {
            $parts[] = $queryKey . '→' . $spec['target'];
        }

        return $parts === [] ? '(пусто)' : implode(', ', $parts);
    }
}

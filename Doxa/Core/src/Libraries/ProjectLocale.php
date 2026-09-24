<?php

namespace Doxa\Core\Libraries;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

/**
 * Канал Chlo и cookie локали проекта (_project_locale).
 * Гарантирует валидную cookie относительно локалей текущего канала.
 */
class ProjectLocale
{
    /**
     * Возвращает имя cookie локали из конфига.
     */
    public static function cookieName(): string
    {
        return (string) config('doxa.locale.cookie', '_project_locale');
    }

    /**
     * Возвращает срок жизни cookie в минутах.
     */
    public static function cookieMinutes(): int
    {
        return (int) config('doxa.locale.cookie_minutes', 60 * 24 * 30 * 12);
    }

    /**
     * Инициализирует Chlo и выставляет канал по host запроса.
     * Возвращает объект канала или null, если канал не найден.
     */
    public static function ensureChannel(): ?object
    {
        if (!Chlo::isInstance()) {
            Chlo::init();
        }

        $channel = Chlo::getCurrentChannel();
        if (!$channel) {
            $channel = Chlo::getChannelByHostAndSetAsCurrent();
        }

        return $channel ?: null;
    }

    /**
     * Возвращает код локали канала по умолчанию (флаг default, иначе первая активная).
     *
     * @throws \RuntimeException если у канала нет локалей
     */
    public static function channelDefaultCode(object $channel): string
    {
        $locales = collect($channel->locales ?? []);
        if ($locales->isEmpty()) {
            throw new \RuntimeException('Channel has no locales: ' . ($channel->code ?? $channel->id ?? '?'));
        }

        $default = $locales->first(function ($locale) {
            return !empty($locale->default);
        });

        return (string) ($default ? $default->code : $locales->first()->code);
    }

    /**
     * Проверяет, есть ли код среди активных локалей канала.
     */
    public static function channelHasCode(object $channel, string $code): bool
    {
        return collect($channel->locales ?? [])->contains('code', $code);
    }

    /**
     * Проверяет, входит ли код в список распространённых языков из конфига.
     */
    public static function isKnownCode(string $code): bool
    {
        $known = config('doxa.locale.known_codes', []);
        if (!is_array($known)) {
            return false;
        }

        return in_array($code, $known, true);
    }

    /**
     * Читает cookie; если пустая или код не из канала — пишет дефолт канала.
     * Выставляет Chlo и app()->setLocale на итоговый код.
     * Возвращает итоговый код локали.
     *
     * @throws \RuntimeException если канал не найден или без локалей
     */
    public static function ensureCookieAndLocale(): string
    {
        $channel = self::ensureChannel();
        if (!$channel) {
            throw new \RuntimeException('Channel not found for host');
        }

        $cookieName = self::cookieName();
        $fromCookie = request()->cookie($cookieName);
        $code = is_string($fromCookie) ? trim($fromCookie) : '';

        if ($code === '' || !self::channelHasCode($channel, $code)) {
            $code = self::channelDefaultCode($channel);
            Cookie::queue($cookieName, $code, self::cookieMinutes());
        }

        Chlo::set(locale: $code);
        app()->setLocale($code);

        return $code;
    }

    /**
     * Возвращает код из cookie (уже должен быть валиден после EnsureChannelLocaleCookie).
     * Если по какой-то причине cookie нет — вызывает ensureCookieAndLocale().
     */
    public static function cookieLocaleCode(): string
    {
        $cookieName = self::cookieName();
        $fromCookie = request()->cookie($cookieName);
        $code = is_string($fromCookie) ? trim($fromCookie) : '';

        $channel = Chlo::getCurrentChannel();
        if ($code !== '' && $channel && self::channelHasCode($channel, $code)) {
            return $code;
        }

        return self::ensureCookieAndLocale();
    }

    /**
     * Ставит cookie и текущую локаль приложения/Chlo (после выбора языка из URL).
     * Если пользователь залогинен — синхронизирует user_profiles.locale.
     */
    public static function applyLocale(string $code): void
    {
        Cookie::queue(self::cookieName(), $code, self::cookieMinutes());
        Chlo::set(locale: $code);
        app()->setLocale($code);
        self::persistLocaleToUserProfile($code);
    }

    /**
     * Пишет locale в user_profiles для залогиненного пользователя, если значение изменилось.
     */
    private static function persistLocaleToUserProfile(string $code): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $current = DB::table('user_profiles')->where('user', $userId)->value('locale');
        if ($current === $code) {
            return;
        }

        DB::table('user_profiles')->where('user', $userId)->update([
            'locale' => $code,
            'updated_at' => now(),
        ]);
    }

    /**
     * Собирает путь с префиксом локали или без него (without_prefix).
     *
     * @param  list<string>  $pathSegments  сегменты пути без кода локали
     */
    public static function buildPath(string $localeCode, array $pathSegments = []): string
    {
        $channel = Chlo::getCurrentChannel();
        $locale = null;
        if ($channel) {
            $locale = collect($channel->locales ?? [])->firstWhere('code', $localeCode);
        }

        $withoutPrefix = $locale && !empty($locale->without_prefix);
        $tail = implode('/', array_values(array_filter($pathSegments, fn ($s) => $s !== '' && $s !== null)));

        if ($withoutPrefix) {
            $path = $tail === '' ? '/' : '/' . $tail;
        } else {
            $path = '/' . $localeCode . ($tail === '' ? '' : '/' . $tail);
        }

        if ($query = request()->getQueryString()) {
            $path .= '?' . $query;
        }

        return $path;
    }
}

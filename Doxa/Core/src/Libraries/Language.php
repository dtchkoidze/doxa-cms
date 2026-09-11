<?php

namespace Doxa\Core\Libraries;

/**
 * Вспомогательная логика для LocaleMiddleware: локали канала и разбор URL.
 * Cookie и канал уже подготовлены EnsureChannelLocaleCookie / ProjectLocale.
 */
trait Language
{
    /** @var object|null Текущий канал */
    protected $channel;

    /** @var \Illuminate\Support\Collection|null Локали канала */
    protected $locales;

    /** @var object|null Локаль с without_prefix */
    protected $locale_without_prefix = null;

    /** @var list<string> */
    protected array $errors = [];

    public string $log_name = 'language';

    /**
     * Подтягивает канал и локали из уже инициализированного Chlo.
     * Возвращает true при успехе, false если канала/локалей нет.
     */
    protected function initialize(): bool
    {
        $this->errors = [];

        $this->channel = Chlo::getCurrentChannel();
        if (!$this->channel) {
            $this->channel = ProjectLocale::ensureChannel();
        }

        if (!$this->channel) {
            $this->setError('channel not found');
            return false;
        }

        if (empty($this->channel->locales)) {
            $this->setError('empty locales for channel ' . ($this->channel->code ?? ''));
            return false;
        }

        $this->locales = collect($this->channel->locales);

        $this->locale_without_prefix = $this->locales->first(function ($locale) {
            return !empty($locale->without_prefix);
        });

        return true;
    }

    /**
     * Возвращает код локали из cookie (валидной для канала).
     */
    protected function cookieLocaleCode(): string
    {
        return ProjectLocale::cookieLocaleCode();
    }

    /**
     * Проверяет, есть ли код среди локалей текущего канала.
     */
    protected function isChannelLocale(string $code): bool
    {
        return $this->locales !== null && $this->locales->contains('code', $code);
    }

    /**
     * Проверяет, похож ли сегмент на код языка по конфигу known_codes.
     */
    protected function isKnownLocaleCode(string $code): bool
    {
        return ProjectLocale::isKnownCode($code);
    }

    /**
     * Собирает путь: локаль из cookie + сегменты без префикса языка.
     *
     * @param  list<string>  $pathSegments
     */
    protected function buildPathWithCookieLocale(array $pathSegments = []): string
    {
        return ProjectLocale::buildPath($this->cookieLocaleCode(), $pathSegments);
    }

    /**
     * Собирает путь без первого сегмента (locale / alias) — для without_prefix.
     */
    protected function buildPathWithoutLocalePrefix(): string
    {
        $segments = request()->segments();
        if (!empty($segments)) {
            array_shift($segments);
        }

        $path = '/' . implode('/', $segments);
        if ($path === '/') {
            // уже корень
        }

        if ($query = request()->getQueryString()) {
            $path .= '?' . $query;
        }

        return $path === '' ? '/' : $path;
    }

    /**
     * Пишет cookie и выставляет текущую локаль (Chlo + app).
     */
    public function setCookie(string $locale): void
    {
        ProjectLocale::applyLocale($locale);
    }

    /**
     * Добавляет ошибку инициализации.
     */
    protected function setError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Возвращает накопленные ошибки.
     *
     * @return list<string>
     */
    protected function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Возвращает ошибки одной строкой.
     */
    protected function getErrorsString(string $delimiter = '; '): string
    {
        return implode($delimiter, $this->errors);
    }
}

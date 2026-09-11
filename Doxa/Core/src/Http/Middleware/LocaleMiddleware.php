<?php

namespace Doxa\Core\Http\Middleware;

use Closure;
use Doxa\Core\Libraries\Chlo;
use Doxa\Core\Libraries\Language;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\Core\Libraries\ProjectLocale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Синхронизация языка URL с cookie и Chlo (алиас public_locale).
 * Ожидает, что EnsureChannelLocaleCookie уже выставил валидную cookie.
 */
class LocaleMiddleware
{
    use Language;

    private bool $log = false;

    /** @var list<string> Пути без языковой логики */
    private array $exceptions = [
        'sitemap.xml',
        'manifest.webmanifest',
    ];

    /** @var array<string, string> Алиас первого сегмента → код локали канала */
    private array $locale_aliases = [
        'ge' => 'ka',
    ];

    /**
     * Разбирает префикс локали в URL, редиректит при необходимости, выставляет локаль.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->log && Clog::write($this->log_name, 'Url: ' . $request->url(), Clog::DEBUG);

        // sitemap.xml и т.п. — без языкового префикса
        if ($this->isLocaleException($request)) {
            if ($redirect = $this->exceptionRedirectIfNested($request)) {
                return $redirect;
            }

            return $next($request);
        }

        // ge → ka и т.п.
        if ($aliasRedirect = $this->handleLocaleAlias($request)) {
            return $aliasRedirect;
        }

        if (!$this->initialize()) {
            abort(500, $this->getErrorsString());
        }

        if (!config('app.multilanguage')) {
            return $next($request);
        }

        $first = (string) ($request->segment(1) ?? '');

        // URL без языкового сегмента (корень /)
        if ($first === '') {
            return $this->handleMissingLocalePrefix($next, $request);
        }

        // Локаль канала в URL
        if ($this->isChannelLocale($first)) {
            return $this->handleChannelLocalePrefix($first, $next, $request);
        }

        // Похоже на язык, но канал его не поддерживает → локаль из cookie + хвост пути
        // /de/about → /en/about (не /en/de)
        if ($this->isKnownLocaleCode($first)) {
            $rest = array_slice($request->segments(), 1);
            $path = $this->buildPathWithCookieLocale($rest);
            $this->log && Clog::write($this->log_name, 'unsupported locale prefix, redirect to cookie locale: ' . $path, Clog::DEBUG);

            return redirect($path, 301);
        }

        // Обычный путь без префикса языка → дописать локаль из cookie
        // /about → /en/about
        $path = $this->buildPathWithCookieLocale($request->segments());
        $this->log && Clog::write($this->log_name, 'no locale prefix, redirect: ' . $path, Clog::DEBUG);

        return redirect($path, 301);
    }

    /**
     * Корень без сегментов: without_prefix или редирект на локаль из cookie.
     *
     * @param  Closure(Request): Response  $next
     */
    private function handleMissingLocalePrefix(Closure $next, Request $request): Response
    {
        if ($this->locale_without_prefix) {
            $code = (string) $this->locale_without_prefix->code;
            $this->log && Clog::write($this->log_name, 'without_prefix locale: ' . $code, Clog::DEBUG);
            $this->setCurrentLocale($code);

            return $next($request);
        }

        $path = $this->buildPathWithCookieLocale([]);
        $this->log && Clog::write($this->log_name, 'redirect WithCookieLocale: ' . $path, Clog::DEBUG);

        return redirect($path, 301);
    }

    /**
     * Первый сегмент — локаль канала.
     *
     * @param  Closure(Request): Response  $next
     */
    private function handleChannelLocalePrefix(string $code, Closure $next, Request $request): Response
    {
        // Явный префикс у without_prefix-локали → срезать префикс
        if ($this->locale_without_prefix && $code === (string) $this->locale_without_prefix->code) {
            $path = $this->buildPathWithoutLocalePrefix();
            $this->log && Clog::write($this->log_name, 'strip without_prefix from url: ' . $path, Clog::DEBUG);

            return redirect($path, 301);
        }

        $this->setCurrentLocale($code);

        return $next($request);
    }

    /**
     * Редирект по алиасу первого сегмента (например ge → ka).
     */
    private function handleLocaleAlias(Request $request): ?Response
    {
        $first = (string) ($request->segment(1) ?? '');
        if ($first === '' || !isset($this->locale_aliases[$first])) {
            return null;
        }

        $localeCode = $this->locale_aliases[$first];
        $this->log && Clog::write($this->log_name, 'Url contains alias ' . $first . ' for ' . $localeCode, Clog::DEBUG);

        if (!$this->initialize()) {
            abort(500, $this->getErrorsString());
        }

        $isWithoutPrefix = Chlo::isLocaleWithoutPrefix($localeCode);
        if ($isWithoutPrefix === null) {
            $this->log && Clog::write($this->log_name, 'Cant find locale ' . $localeCode, Clog::DEBUG);

            return null;
        }

        if ($isWithoutPrefix) {
            $path = $this->buildPathWithoutLocalePrefix();
            $this->log && Clog::write($this->log_name, 'alias without_prefix redirect: ' . $path, Clog::DEBUG);

            return redirect($path, 301);
        }

        $path = str_replace('/' . $first, '/' . $localeCode, $request->url());
        $this->log && Clog::write($this->log_name, 'alias to code redirect: ' . $path, Clog::DEBUG);

        return redirect($path, 301);
    }

    /**
     * Выставляет текущую локаль и cookie.
     */
    private function setCurrentLocale(string $code): void
    {
        ProjectLocale::applyLocale($code);
    }

    /**
     * Путь относится к исключениям (sitemap и т.п.).
     */
    private function isLocaleException(Request $request): bool
    {
        $segments = collect($request->segments());
        foreach ($this->exceptions as $exception) {
            if ($segments->contains($exception)) {
                if ($exception === 'sitemap.xml' && !config('app.sitemap')) {
                    abort(404);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Вложенный путь к исключению (/xx/sitemap.xml) → редирект на корень файла.
     */
    private function exceptionRedirectIfNested(Request $request): ?Response
    {
        $segments = collect($request->segments());
        foreach ($this->exceptions as $exception) {
            if ($segments->contains($exception) && $segments->count() !== 1) {
                return redirect('/' . $exception);
            }
        }

        return null;
    }
}

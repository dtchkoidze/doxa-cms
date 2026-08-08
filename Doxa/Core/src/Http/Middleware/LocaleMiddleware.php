<?php

namespace Doxa\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Doxa\Core\Libraries\Chlo;
use Doxa\Core\Libraries\Language;
use Doxa\Core\Libraries\Logging\Clog;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    use Language;

    private Request $request;

    private bool $log = false;

    private $exceptions = [
        'sitemap.xml',
        'manifest.webmanifest',
    ];

    private $locale_aliases = [
        'ge' => 'ka',
    ];

    /**
     * Handle an incoming request.
     *
     * Supports locales.without_prefix (e.g. ka at / without /ka).
     * Sites without such a locale keep the previous redirect-to-default behaviour.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->log && Clog::write($this->log_name, 'Url: ' . $request->url(), Clog::DEBUG);

        if (!empty($this->locale_aliases)) {
            foreach ($this->locale_aliases as $alias => $locale_code) {
                if (request()->segment(1) == $alias) {
                    $this->log && Clog::write($this->log_name, 'Url contains ' . $alias . ' for ' . $locale_code, Clog::DEBUG);
                    $is_locale_without_prefix = Chlo::isLocaleWithoutPrefix($locale_code);
                    if ($is_locale_without_prefix === null) {
                        $this->log && Clog::write($this->log_name, 'Cant find locale ' . $locale_code, Clog::DEBUG);
                        continue;
                    }

                    if ($is_locale_without_prefix) {
                        // Need locales collection for buildPathWithoutLocalePrefix via initialize
                        $this->initialize();
                        $path = $this->buildPathWithoutLocalePrefix();
                        $this->log && Clog::write($this->log_name, 'alias without_prefix redirect: ' . $path, Clog::DEBUG);
                        return redirect($path, 301);
                    }

                    $path = str_replace('/' . $alias, '/' . $locale_code, request()->url());
                    $this->log && Clog::write($this->log_name, 'alias to code redirect: ' . $path, Clog::DEBUG);
                    return redirect($path, 301);
                }
            }
        }

        if ($this->checkExeptions($request, $next)) {
            return $next($request);
        }

        !$this->initialize() && die($this->getErrorsString());

        if (!config('app.multilanguage')) {
            return $next($request);
        }

        $this->log && Clog::write($this->log_name, 'routePrefix: ' . $this->routePrefix, Clog::DEBUG);

        if (!$this->routePrefix) {
            if ($this->locale_without_prefix) {
                $code = $this->locale_without_prefix->code;
                $this->log && Clog::write($this->log_name, 'without_prefix locale: ' . $code, Clog::DEBUG);
                $this->setCurrentLocale($code);
                return $next($request);
            }

            $path = $this->buildPathWithLocalePrefix();
            $this->log && Clog::write($this->log_name, 'redirect WithLocalePrefix: ' . $path, Clog::DEBUG);
            return redirect($path, 301);
        }

        if ($this->locale_without_prefix) {
            $code = $this->locale_without_prefix->code;
            if ($this->routePrefix == $code) {
                $path = $this->buildPathWithoutLocalePrefix();
                $this->log && Clog::write($this->log_name, 'strip without_prefix from url: ' . $path, Clog::DEBUG);
                return redirect($path, 301);
            }
        }

        $this->setCurrentLocale($this->routePrefix);
        return $next($request);
    }

    private function setCurrentLocale($code)
    {
        Chlo::set(locale: $code);
        app()->setLocale($code);
        $this->setCookie($code);
    }

    public function checkExeptions(Request $request, Closure $next)
    {
        if (!empty($this->exceptions)) {
            $_segments = collect(request()->segments());
            foreach ($this->exceptions as $exception) {
                if ($_segments->contains($exception)) {
                    if ($exception == 'sitemap.xml') {
                        if (!config('app.sitemap')) {
                            abort(404);
                        }
                    }

                    if ($_segments->count() != 1) {
                        return redirect('/' . $exception);
                    }

                    return true;
                }
            }
        }
    }
}

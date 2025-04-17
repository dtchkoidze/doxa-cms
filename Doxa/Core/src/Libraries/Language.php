<?php

namespace Doxa\Core\Libraries;

use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Cookie;

trait Language
{
    protected $channel;

    protected $locales;

    protected ?string $defaultLocale;

    protected ?string $routePrefix;

    protected $errors = [];

    protected $config;

    public $log_name = 'language';

    protected int $cookieExpires = 60 * 24 * 30 * 12;

    protected string $cookieName = '_project_locale';

    protected function initialize()
    {
        $this->errors = [];

        Chlo::init();
        $this->channel = Chlo::getChannelByHostAndSetAsCurrent();

        if (!$this->channel) {
            Clog::write($this->log_name, 'channel not found');
            Clog::write('critical', 'channel not found');
            $this->setError('channel not found');
            //die('Current channel not found. Please, add current host to channels table.');
            return false;
        }

        if (empty($this->channel->locales)) {
            Clog::write($this->log_name, 'empty locales for channel ' . $this->channel->code);
            Clog::write('critical', 'empty locales for channel ' . $this->channel->code);
            $this->setError('empty locales for channel ' . $this->channel->code);
            return false;
        }

        $this->locales = collect($this->channel->locales);

        $this->getDefaultLocale();

        $this->routePrefix = request()->segment(1);

        if ($this->routePrefix) {
            if ($this->locales->doesntContain('code', $this->routePrefix)) {
                // Changed this, routePrefix was being assigned an empty string.
                $this->routePrefix = '';
            }
        }

        return true;
    }

    protected function getDefaultLocale()
    {
        $this->defaultLocale = $this->tryGetLocaleFromCookie();
        if (!$this->defaultLocale) {
            $this->defaultLocale = $this->getPreferredLocale();
        }
        if (!$this->defaultLocale) {
            $this->defaultLocale = $this->locales->has('default', 1);
        }
    }

    private function getPreferredLocale(): string|null
    {
        $lng = request()->getPreferredLanguage();
        $lng = (strpos($lng, '_') !== false) ? explode('_', $lng)[0] : ((strpos($lng, '-') !== false) ? explode('-', $lng)[0] : $lng);
        $preferredLanguageCode = ($this->locales->contains('code', $lng)) ? $lng : null;
        return $preferredLanguageCode;
    }

    protected function buildPathWithLocalePrefix()
    {
        $segments = request()->segments();
        array_unshift($segments, $this->defaultLocale);
        return '/'.implode('/', $segments);
    }

    protected function buildLngPath($locale = '')
    {
        $redirectPath = '';
        if ($locale) {
            $redirectPath = $locale;
        }

        $segments = request()->segments();

        if ($this->routePrefix) {
            array_shift($segments);
        }

        // Added lines 96-100 to check if the locale is provided but isn't in the current channels locale list. If so, remove from url
        $locales = core()->getCurrentChannel()->locales->pluck('code')->toArray();

        if (isset($segments[0]) && !in_array($segments[0], $locales)) {
            array_shift($segments);
        }

        if ($path = implode('/', $segments)) {
            $redirectPath .= '/' . $path;
        }

        if ($query = request()->getQueryString()) {
            $redirectPath .= '?' . $query;
        }

        return $redirectPath;
    }

    public function setCookie($locale)
    {
        Cookie::queue($this->cookieName, $locale, $this->cookieExpires);
    }

    private function tryGetLocaleFromCookie(): string|null
    {
        $locale = request()->cookie($this->cookieName);
        if ($locale) {
            if (!$this->locales->contains('code', $locale)) {
                $locale = null;
            }
        }
        return $locale;
    }

    protected function setError($error)
    {
        $this->errors[] = $error;
    }

    protected function getErrors()
    {
        return $this->errors;
    }

    protected function getErrorsString($delimiter = '; ')
    {
        return implode($delimiter, $this->errors);
    }
}

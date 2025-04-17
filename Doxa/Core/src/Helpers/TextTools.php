<?php

namespace Doxa\Core\Helpers;

use Doxa\Core\Libraries\Chlo;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Cache;

class TextTools
{
    /**
     * Defined in extends
     *
     * @var string
     */
    protected string $prefix = '';

    protected string $locale = '';

    protected string $key = '';

    protected string $cache_key = '';

    protected int $cache_expire = 60 * 24 * 365;


    public function get(string $key, array $variables = [], string $locale = '')
    {

        $this->key = $key;

        $this->locale = $locale ?: Chlo::getCurrentLocaleCode();

        // dd($this->locale);

        $value = $this->getFromCache($this->key, $this->locale);

        $toCache = (!$value) ? true : false;

        if (!$value) {
            $value = $this->find();
        }

        if ($value && $toCache) {
            $this->addToCache($this->key, $this->locale, $value);
        }

        if ($value && !empty($variables)) {
            // dump($value && !empty($variables));
            $value = $this->replaceVariables($value, $variables);
            // dump($value);
        }

        return $value ?: $this->prefix . '.' . $key;
    }

    protected function getFromCache($key, $locale)
    {
        return Cache::get($this->getCacheKey($key, $locale));
    }

    protected function find()
    {
        if ($value = $this->findInDB()) {
            return $value;
        }
        if ($value = $this->findInConfig()) {
            return $value;
        }
        return false;
    }

    protected function findInConfig()
    {
        $config = config($this->prefix . '.' . $this->locale);
        return !empty($config[$this->key]) ? $config[$this->key] : false;
    }

    protected function replaceVariables($value, array $variables)
    {
        /**
         * I changed str_replace. Was this: $value = str_replace('{' . $this->prefix . '.' . $key . '}', $val, $value);
         * I don't see why do I need prefix here, or the brackets, the $key already has brackets. 
         */
        foreach ($variables as $key => $val) {
            $value = str_replace('{' . $key . '}', $val, $value);
        }
        return $value;
    }

    public function getCacheKey($key, $locale)
    {
        return $this->prefix . '.' . $locale . '.' . $key;
    }

    public function addToCache($key, $locale, $value)
    {
        //Clog::write('test900', 'put in cache key = ' . $this->getCacheKey($key, $locale) . ': ' . $value);
        Cache::put($this->getCacheKey($key, $locale), $value, $this->cache_expire);
    }
}



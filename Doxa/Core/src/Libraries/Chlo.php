<?php

namespace Doxa\Core\Libraries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Repositories\Repository;
use Doxa\Core\Libraries\Package\Package;
use Doxa\Modules\Locale\Repositories\Locale;
use Doxa\Modules\Channel\Repositories\Channel;

class Chlo
{
    public static self|null $instance = null;

    private ?Channel $channelRepository = null;

    private ?Locale $localeRepository = null;

    private array $channels;

    private array $locales;

    private array $assoc_channels;

    private array $assoc_locales;

    private ?object $channel = null;

    private ?object $locale = null;

    private ?object $default_channel = null;

    private ?object $default_locale = null;

    private string $reguested_channel_key = '';
    private string|int $reguested_channel_val = '';

    private string $reguested_locale_key = '';
    private string|int $reguested_locale_val = '';

    private $without_channels = false;

    private final function  __construct()
    {
        //dump( __CLASS__ . " initializes only once\n" );
    }

    public static function init($module = false, $channel = false, $locale = false, $reset = false)
    {
        if (!isset(self::$instance) || $reset) {
            self::$instance = new Chlo();
            self::$instance->channelRepository = moduleRepository('channel');
            self::$instance->localeRepository = moduleRepository('locale');
            self::$instance->initialize();
        }

        if ($channel) {
            self::$instance->_set(channel: $channel);
        }

        if ($locale) {
            self::$instance->_set(locale: $locale);
        }

        return self::$instance;
    }

    public static function test1()
    {
        dump('test1');
    }

    public static function isInstance()
    {
        return self::$instance !== null;
    }

    public static function set($channel = false, $locale = false)
    {
        self::$instance->_set($channel, $locale);
    }

    private function _set($channel = false, $locale = false)
    {
        if ($channel) {
            if (is_array($channel)) {
                $this->reguested_channel_key = $channel['key'];
                $this->reguested_channel_val = $channel['val'];
            } else {
                if ((int) $channel == $channel) {
                    $this->reguested_channel_key = 'id';
                    $this->reguested_channel_val = (int) $channel;
                } else {
                    $this->reguested_channel_key = 'host';
                    $this->reguested_channel_val = $channel;
                }
            }

            $this->channel = $this->tryGetChannel();

            if (!$this->channel) {
                $this->reguested_channel_key = '';
                $this->reguested_channel_val = '';
            }
        }

        if ($locale) {
            if (is_array($locale)) {
                $this->reguested_locale_key = $locale['key'];
                $this->reguested_locale_val = $locale['val'];
            } else {
                if ((int) $locale == $locale) {
                    $this->reguested_locale_key = 'id';
                    $this->reguested_locale_val = (int) $locale;
                } else {
                    $this->reguested_locale_key = 'code';
                    $this->reguested_locale_val = $locale;
                }
            }

            $this->locale = $this->tryGetLocale();

            if (!$this->locale) {
                $this->reguested_locale_key = '';
                $this->reguested_locale_val = '';
            }
        }

        // dump([
        //     '$this->reguested_channel_key' => $this->reguested_channel_key,
        //     '$this->reguested_channel_val' => $this->reguested_channel_val,
        //     '$this->reguested_locale_key' => $this->reguested_locale_key,
        //     '$this->reguested_locale_val' => $this->reguested_locale_val,
        // ]);

    }

    public static function withoutChannels()
    {
        self::$instance->without_channels = true;
        return self::$instance;
    }

    public static function asAssocById($without_channels = false): array
    {
        return self::$instance->_asAssocById($without_channels);
    }

    private function _asAssocById($without_channels)
    {
        if (!$without_channels) {
            return [
                'channels' => $this->assoc_channels,
            ];
        }

        if (!$without_channels) {
            return [
                'channels' => $this->assoc_channels,
            ];
        } else {
            return [
                'locales' => $this->assoc_locales,
            ];
        }
    }

    public static function altAsAssocById($without_channels = false)
    {
        return self::$instance->_altAsAssocById($without_channels);
    }

    public static function getChannelsWithLocales()
    {
        return self::$instance->channels;
    }

    private function _altAsAssocById($without_channels)
    {
        if (!$without_channels) {
            $without_channels = $this->without_channels;
        }

        if (!$without_channels) {
            //dd($this->assoc_channels);
            return $this->assoc_channels;
        } else {
            $channels = [
                0 => (object)[
                    'id' => 0,
                    'name' => '',
                    'locales' => $this->assoc_locales
                ],
            ];
            //dd($channels);
            return $channels;
        }

        // if(!$without_channels){
        //     return [
        //         'channels' => $this->assoc_channels,
        //     ];
        // } else {
        //     return [
        //         'locales' => $this->assoc_locales,
        //     ];
        // }

    }

    public static function getCurrentChannelId()
    {
        if (self::$instance->channel) {
            return self::$instance->channel->id;
        }
        return 0;
    }

    public static function getCurrentChannelCode()
    {
        if (is_null(self::$instance)) {
            self::init();
        }
        if (self::$instance->channel) {
            return self::$instance->channel->code;
        }
        return '';
    }

    public static function getDefaultChannelId()
    {
        return self::$instance->default_channel->id;
    }

    public static function getCurrentLocaleId()
    {
        if (is_null(self::$instance)) {
            self::init();
        }
        if (self::$instance->locale) {
            return self::$instance->locale->id;
        }
        // --- 14.08.2025
        if (self::$instance->default_locale) {
            return self::$instance->default_locale->id;
        }
        return 0;
    }

    public static function getCurrentLocaleCode()
    {
        if (self::$instance === null) {
            self::init();
        }

        if (self::$instance->locale) {
            return self::$instance->locale->code;
        }

        return app()->getLocale();

        //return '';
    }

    public static function getLocaleCodeById($id)
    {
        return self::$instance->_getLocaleCodeById($id);
    }

    private function _getLocaleCodeById($id)
    {
        return $this->assoc_locales[$id]->code;
    }

    public static function setDefaultChannelAsCurrent()
    {
        self::$instance->channel = self::$instance->default_channel;
    }

    public static function setDefaultLocaleAsCurrent(): void
    {
        self::$instance->getDefaultLocale();
        self::$instance->locale = self::$instance->default_locale;
    }

    public static function getChannelByHostAndSetAsCurrent()
    {
        return self::$instance->_getChannelByHostAndSetAsCurrent();
    }

    public static function getCurrentChannelLocales()
    {
        return self::$instance->channel->locales;
    }

    private function _getChannelByHostAndSetAsCurrent()
    {
        $hostname = request()->getHttpHost();

        $this->channel = $this->channelRepository->mm->with('locales')->where('host', 'in', [
            "'" . $hostname . "'",
            "'" . 'http://' . $hostname . "'",
            "'" . 'https://' . $hostname . "'",
        ])->first();

        return $this->channel;
    }

    public static function isChannelExists($id)
    {
        foreach (self::$instance->channels as $channel) {
            if ($channel->id == $id) {
                return $channel;
            }
        }
        return false;
    }

    public static function isLocaleExists($val)
    {
        self::$instance->set(locale: $val);
        if (self::$instance->locale) {
            return self::$instance->locale;
        }
        return false;
    }

    private function initialize()
    {
        $this->channels = $this->channelRepository->manager->with('locales')->where('active', 1)->get();
        $this->getDefaultChannel();
        if ($this->channels) {
            foreach ($this->channels as &$channel) {
                $this->assoc_channels[$channel->id] = $channel;
                $_locales = [];
                foreach ($channel->locales as $locale) {
                    $_locales[$locale->id] = $locale;
                }
                $channel->locales = $_locales;
            }
        }

        $this->locales = $this->localeRepository->manager->where('active', 1)->get();
        $this->getDefaultLocale();
        if ($this->locales) {
            foreach ($this->locales as $locale) {
                $this->assoc_locales[$locale->id] = $locale;
            }
        }

        // dump([
        //     '$this->channels' => $this->channels,
        //     '$this->locales' => $this->locales,
        //     '$this->default_channel' => $this->default_channel,
        //     '$this->default_locale' => $this->default_locale,
        // ]);
    }

    private function initialize_OLD()
    {
        $this->channels = $this->channelRepository->manager->with('locales')->where('active', 1)->get();
        $this->getDefaultChannel();
        if ($this->channels) {
            foreach ($this->channels as &$channel) {
                $this->assoc_channels[$channel->id] = $channel;
                $_locales = [];
                foreach ($channel->locales as $locale) {
                    $_locales[$locale->id] = $locale;
                }
                $channel->locales = $_locales;
            }
        }

        $this->locales = $this->localeRepository->manager->where('active', 1)->get();
        $this->getDefaultLocale();
        if ($this->locales) {
            foreach ($this->locales as $locale) {
                $this->assoc_locales[$locale->id] = $locale;
            }
        }

        // dump([
        //     '$this->channels' => $this->channels,
        //     '$this->locales' => $this->locales,
        //     '$this->default_channel' => $this->default_channel,
        //     '$this->default_locale' => $this->default_locale,
        // ]);
    }

    private function getDefaultChannel()
    {
        foreach ($this->channels as $k => $channel) {
            $k == 0 && $first_channel = $channel;
            if ((bool)$channel->default) {
                $this->default_channel = $channel;
            }
        }
        if (!$this->default_channel) {
            $this->default_channel = $first_channel;
        }
    }

    private function getDefaultLocale()
    {
        if ($this->channel) {
            $locales = $this->channel->locales;
        } else {
            $locales = $this->locales;
        }
        foreach ($locales as $k => $locale) {
            $k == 0 && $first_locale = $locale;
            if ((bool)$locale->default) {
                $this->default_locale = $locale;
            }
        }
        if (!$this->default_locale) {
            $this->default_locale = $first_locale;
        }
    }

    private function tryGetChannel()
    {
        foreach ($this->channels as $channel) {
            if ($channel->{$this->reguested_channel_key} == $this->reguested_channel_val) {
                return $channel;
            }
        }

        return null;
    }

    private function tryGetLocale()
    {
        if ($this->channel) {
            $locales = $this->channel->locales;
        } else {
            $locales = $this->locales;
        }

        foreach ($locales as $locale) {
            if ($locale->{$this->reguested_locale_key} == $this->reguested_locale_val) {
                return $locale;
            }
        }

        return null;
    }
}

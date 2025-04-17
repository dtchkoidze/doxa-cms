<?php

namespace Doxa\Admin\View\Components;

use Illuminate\View\Component;
use Webkul\Core\Models\Locale;
use Doxa\Core\Helpers\Logging\Clog;
use Projects\PriveWebsite\Helpers\Language;

class LocaleSwitcher extends Component
{
    protected $admin_locale_cookie = 'admin_locale';

    protected $locales = [
        'en',
        'ge',
        'ru'
    ];

    protected $default_locale = 'en';

    protected $locales_list;

    protected ?string $current_locale;

    public function __construct(protected string $direction = 'down')
    {
        $this->getLocales();
        $this->getCurrentLocale();
    }

    public function getLocales()
    {
        $this->locales_list = Locale::whereIn('code', $this->locales)->get();
    }

    public function getCurrentLocale()
    {
        $this->current_locale = @$_COOKIE['admin_locale'];
        !$this->current_locale && $this->current_locale = app()->getLocale();
        app()->setLocale($this->current_locale);
    }

    public function render()
    {
        $locales = [
            'current' => '',
            'others' => [],
        ];

        foreach ($this->locales_list as $locale) {

            if ($locale->code == $this->current_locale) {
                $locales['current'] = $locale;
            } else {
                $locales['others'][] = $locale;
            }
        }

        return view('doxa-admin::components.locale-switcher')->with([
            'locales' => $locales,
            'direction' => $this->direction,
            'cookie_name' => $this->admin_locale_cookie
        ]);
    }


}

<?php

use Doxa\Admin\Facades\OmniTranslator;

if (!function_exists('omniTrans')) {
    function omniTrans()
    {
        return OmniTranslator::trans(...func_get_args());
    }
}

if (!function_exists('omniPageTitle')) {
    function omniPageTitle()
    {
        return OmniTranslator::transPageTitle(...func_get_args());
    }
}

if (!function_exists('omniModuleTrans')) {
    function omniModuleTrans()
    {
        return OmniTranslator::moduleTrans(...func_get_args());
    }
}

if (!function_exists('adminLocale')) {
    function adminLocale()
    {
        if(isset($_COOKIE['admin_locale'])){
            app()->setLocale($_COOKIE['admin_locale']);
            return $_COOKIE['admin_locale'];
        }
        return 'en';
    }
}

if (!function_exists('printArr')) {
    function printArr($arr)
    {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }
}


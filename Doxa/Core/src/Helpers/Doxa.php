<?php

namespace Doxa\Core\Helpers;


class Doxa
{
    private static $path_to_doxa_package = 'vendor/doxa/doxa-cms/Doxa/';

    public static function path_to_doxa_modules() {
        return base_path(self::$path_to_doxa_package . 'Modules/');
    }
}

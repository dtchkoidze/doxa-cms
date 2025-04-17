<?php

namespace Doxa\Libraries;

use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Projects\Dusty\Libraries\Partner\Partner_Venue;
use Projects\Dusty\Libraries\Partner\Partner_Creative;


class Utils
{
    public static function getCustomAuthOptions($key)
    {
        return config('project_options.custom_auth.'.$key) ?? [];
    }

    public static function getLogsLifetime()
    {
        return 60 * 60 * 24 * 1;
    }
    
}

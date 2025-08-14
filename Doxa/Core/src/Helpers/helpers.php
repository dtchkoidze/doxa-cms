<?php

use Doxa\Core\Facades\Pvar;
use Doxa\Core\Facades\Vocab;
use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Carbon;
use Doxa\Core\Facades\TextBlock;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Cache;
use Doxa\Core\Repositories\Repository;
use Illuminate\Support\Facades\Storage;
use Doxa\Core\Libraries\Package\Package;
use Doxa\Core\Services\PermissionChecker;
use Doxa\Admin\Libraries\Configuration\Configuration;

if (!function_exists('vocab')) {
    function vocab()
    {
        //Clog::write('vocab', 'vocab()', 3);
        return Vocab::get(...func_get_args());
    }
}

if (!function_exists('textblock')) {
    function textblock()
    {
        //Clog::write('vocab', 'textblock()', 3);
        return TextBlock::get(...func_get_args());
    }
}

if (!function_exists('snakeToCamel')) {
    function snakeToCamel($snake, $firstToUppercase = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $snake)));
        if (!$firstToUppercase) {
            $str = lcfirst($str);
        }
        return $str;
    }
}

if (!function_exists('generateSecret')) {
    function generateSecret($length = 6)
    {
        $include_chars = "0123456789";
        $charLength = strlen($include_chars);
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $include_chars[rand(0, $charLength - 1)];
        }
        return $string;
    }
}

if (!function_exists('moduleRepository')) {
    function moduleRepository(String $module)
    {
        $package = new Package($module);
        $paths = $package->getPaths();

        if (!empty($paths)) {
            $class_file_path = $paths['dir_path'] . '/Repositories/' . $package->getPackageName() . '.php';
            if (file_exists($class_file_path)) {
                require_once $class_file_path;
                $class = $paths['class'];
                return new $class($package);
            }
        }

        return new Repository($package);
    }
}

if (!function_exists('mr')) {
    function mr(String $module)
    {
        $package = new Package($module);
        $paths = $package->getPaths();

        if (!empty($paths)) {
            $class_file_path = $paths['dir_path'] . '/Repositories/' . $package->getPackageName() . '.php';
            if (file_exists($class_file_path)) {
                require_once $class_file_path;
                $class = $paths['class'];
                return new $class($package);
            }
        }

        return new Repository($package);
    }
}

if (!function_exists('user_can')) {
    function user_can($permission_group)
    {
        $method_name = 'can' . ucfirst(strtolower($permission_group));
        return (new PermissionChecker)->$method_name();
    }
}

if (!function_exists('chlo')) {
    function chlo()
    {
        return Chlo::$instance;
    }
}

if (!function_exists('get_view')) {
    function get_view($name)
    {

        // dd($name);

        $channelCode = chlo()->getCurrentChannelCode();
        $localeCode = chlo()->getCurrentLocaleCode();

        $viewNames = Cache::get('viewNames');
        // dd($viewNames);
        $viewNames = '';

        $prefix = strtolower(config('app.project_name')) . '::';

        if (!$viewNames) {
            $storage = Storage::disk('view_directory');
            // dd($storage);
            $viewNames = [];
            foreach ($storage->allDirectories() as $folder) {
                foreach ($storage->allFiles($folder) as $file) {
                    $viewNames[] = $prefix . str_replace('/', '.', str_replace('.blade.php', '', $file));
                }
            }
            Cache::put('viewNames', $viewNames, 60);
        }

        $files = [
            $prefix . $channelCode . '.' . $localeCode . '.' . $name,
            $prefix . $channelCode . '.any.' . $name,
            $prefix . 'default.' . $localeCode . '.' . $name,
            $prefix . 'default.any.' . $name,
            $prefix . $name,
        ];

        foreach ($files as $file) {
            if (in_array($file, $viewNames)) {
                // Log::info('View served from cache', ['view' => $file]);
                // dd($file);
                return $file;
            }
        }
    }
}


if (!function_exists('pvar')) {
    function pvar()
    {
        return Pvar::get(...func_get_args());
    }
}

if (!function_exists('projectTitle')) {
    function projectTitle()
    {
        return config('project_options.project_title');
    }
}

/**
 * First "c" stands for custom
 */
if (!function_exists('cconfig')) {
    function cconfig()
    {
        $item = app(Configuration::class)->get(func_get_arg(0));

        if (empty($item)) {
            $arg = func_get_arg(0);
            return __METHOD__ . $arg;
        }

        return $item;
    }
}

if (!function_exists('fmt_carbon_humanized')) {
    function fmt_carbon_humanized($date, $locale)
    {

        switch ($locale) {
            case 'ka':
                $locale = 'ka_GE';
                break;
            case 'en':
                $locale = 'en_US';
                break;
            case 'ru':
                $locale = 'ru_RU';
                break;
            default:
                $locale = 'ka_GE';
        }
        Carbon::setLocale($locale);
        $date = Carbon::parse($date);
        $now = Carbon::now();

        $diffInHours = $now->diffInHours($date);

        if ($diffInHours < 24) {
            return $date->diffForHumans();
        } else {
            return $date->format('d-m-Y H:i');
        }
    }
}

// $path_to_doxa = 'vendor/doxa/doxa-cms/Doxa/';

// if (!function_exists('get_doxa_modules_dir_path')) {
//     function get_doxa_modules_dir_path()
//     {
//         return base_path($path_to_doxa . 'Modules');
//     }
// }

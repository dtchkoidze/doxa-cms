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

if (!function_exists('toCamel')) {
    function toCamel($string)
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
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


use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

if (!function_exists('slog')) {
    /**
     * Table-style log writer with clickable path and accurate caller detection
     *
     * @param string|array $fileNames
     * @param string $type [error, critical, info, warning, debug]
     * @param mixed $message
     */
    function slog($fileNames, string $type, $message): void
    {
        $fileNames = is_array($fileNames) ? $fileNames : [$fileNames];

        $levels = [
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
        ];
        $level = $levels[$type] ?? Logger::INFO;

        // get real caller (skip slog itself)
        $traceList = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $caller = null;

        foreach ($traceList as $trace) {
            if (
                isset($trace['file']) &&
                !str_contains($trace['file'], 'helpers') &&
                !str_contains($trace['file'], 'vendor') &&
                !str_contains($trace['file'], 'Middleware') &&
                ($trace['function'] ?? '') !== 'slog'
            ) {
                $caller = $trace;
                break;
            }
        }

        $file  = $caller['file'] ?? 'unknown';
        $line  = $caller['line'] ?? 'unknown';
        $class = $caller['class'] ?? '';
        $func  = $caller['function'] ?? '';
        $method = $class ? "{$class}::{$func}" : $func;

        // Table formatting
        $date = date('d M H:i:s');

        // Convert arrays/objects to string
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        // Split multi-line messages properly
        $messageLines = explode("\n", trim($message));
        $firstLine = array_shift($messageLines);

        $logLine =
            str_pad($date, 15, ' ', STR_PAD_RIGHT) . ' | ' .
            str_pad($method, 60, ' ', STR_PAD_RIGHT) . ' | ' .
            str_pad($line, 5, ' ', STR_PAD_LEFT) . ' | ' .
            $firstLine . "\n";

        foreach ($messageLines as $ml) {
            if (trim($ml) !== '') {
                $logLine .= str_repeat(' ', 15) . ' | ' .
                    str_repeat(' ', 60) . ' | ' .
                    str_repeat(' ', 5) . ' | ' .
                    $ml . "\n";
            }
        }

        // Write to file(s)
        foreach ($fileNames as $name) {
            $logDate = date('Y-m-d');
            $logPath = storage_path("logs/{$name}__{$logDate}.log");

            @clearstatcache();
            $log_size = @filesize($logPath) ?: 0;
            $fileHandle = @fopen($logPath, 'a');
            if (!$fileHandle) {
                continue;
            }

            // header for new log file
            if (empty($log_size)) {
                fputs($fileHandle, "      Date      |                           METHOD                         | LINE | Log\n");
                fputs($fileHandle, "----------------+----------------------------------------------------------+------+-------------------------------------------------------------\n");
            }

            fputs($fileHandle, $logLine);
            fflush($fileHandle);
            fclose($fileHandle);
        }
    }
}
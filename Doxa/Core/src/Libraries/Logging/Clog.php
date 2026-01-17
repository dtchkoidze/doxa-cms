<?php

namespace Doxa\Core\Libraries\Logging;

use Exception;
use Illuminate\Support\Facades\Log;

class Clog
{
    const ERROR = 1;

    const WARNING = 2;

    const NOTICE = 3;

    const DEBUG = 4;

    /**
     *
     * Log thresholds:
     *  0 - Disable logging
     *  1 - Errors and exceptions
     *  2 - Warnings
     *  3 - Notices
     *  4 - Debugging
     *
     * @return void
     */
    public static function write()
    {
        if (!($args_num = func_num_args()) or !($args_arr = func_get_args())) {
            return false;
        }

        $file_prefix = array_shift($args_arr);
        if (empty($args_arr)) return false;

        $str = array_shift($args_arr);

        // Определяем, является ли последний аргумент уровнем
        $last_arg = end($args_arr);
        $level = (is_int($last_arg) && $last_arg >= 1 && $last_arg <= 4)
            ? array_pop($args_arr)
            : 1;

        $log_level = config('project_options.log_level');
        if (!$log_level) {
            return false;
        }

        if (!$level) {
            $level = 1;
        }

        if ($log_level < 4 && $level > $log_level) {
            return false;
        }

        // если передан массив — пишем в несколько файлов
        $file_prefixes = is_array($file_prefix) ? $file_prefix : [$file_prefix];

        // форматируем первое сообщение
        if (is_array($str) || is_object($str)) {
            $str = print_r($str, true);
        }

        $params = Clog::GetCallingParams();

        // формируем строку лога
        $lines = explode("\n", trim($str));
        $first_line = array_shift($lines);

        $log_line =
            date('H:i:s') . ' | ' .
            str_pad(self::shortenPath($params['method']), 80, ' ', STR_PAD_RIGHT) . ' | ' .
            str_pad($params['line'], 4, ' ', STR_PAD_RIGHT) . ' | ' .
            $first_line . "\n";

        // дополнительные строки из $str
        foreach ($lines as $extra_line) {
            if (trim($extra_line) !== '') {
                $log_line .=
                    str_repeat(' ', 8) . ' | ' .
                    str_repeat(' ', 80) . ' | ' .
                    str_repeat(' ', 4) . ' | ' .
                    $extra_line . "\n";
            }
        }

        // добавляем дополнительные аргументы (между $str и $level)
        foreach ($args_arr as $extra) {
            if (is_array($extra) || is_object($extra)) {
                $extra = print_r($extra, true);
            }
            $extra_lines = explode("\n", trim((string)$extra));
            foreach ($extra_lines as $extra_line) {
                if (trim($extra_line) !== '') {
                    $log_line .=
                        str_repeat(' ', 8) . ' | ' .
                        str_repeat(' ', 80) . ' | ' .
                        str_repeat(' ', 4) . ' | ' .
                        $extra_line . "\n";
                }
            }
        }

        // запись в файл
        foreach ($file_prefixes as $prefix) {
            $filepath = base_path('storage/logs') . '/' . $prefix . '_' . date('Y-m-d') . '.log';

            @clearstatcache();
            $log_size = @filesize($filepath) ?: 0;

            if (!($fil = @fopen($filepath, 'a'))) {
                continue;
            }

            if (empty($log_size)) {
                fputs($fil, "   Date  |                                       METHOD                                     | LINE | Log\n");
                fputs($fil, "---------+----------------------------------------------------------------------------------+------+-------------------------------------------------------------\n");
            }

            @fputs($fil, $log_line);
            @fflush($fil);
            @fclose($fil);
        }
    }



    /**
     * Shorten long path for log display
     *
     * @param string $str
     * @param int $maxLength
     * @return string
     */
    public static function shortenPath(string $str, int $maxLength = 80): string
    {
        $len = strlen($str);
        if ($len <= $maxLength) {
            return $str;
        }

        // оставляем конец строки + троеточие
        return '...' . substr($str, $len - ($maxLength - 3));
    }


    static function GetCallingParams(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $logCall   = $trace[1] ?? []; // место вызова Clog::write()
        $caller    = $trace[2] ?? []; // кто вызвал write()

        return [
            'file'   => $logCall['file'] ?? '',
            'line'   => $logCall['line'] ?? 0,
            'method' => ($caller['class'] ?? '') . '::' . ($caller['function'] ?? ''),
        ];
    }








    static function GetCallingMethodName()
    {
        $e = new Exception();
        $trace = $e->getTrace();
        //position 0 would be the line that called this function so we ignore it
        $last_call = $trace[1];
        print_r($last_call);
    }

    static function firstCall($a, $b)
    {
        self::theCall($a, $b);
    }

    static function theCall($a, $b)
    {
        self::GetCallingMethodName();
    }
}

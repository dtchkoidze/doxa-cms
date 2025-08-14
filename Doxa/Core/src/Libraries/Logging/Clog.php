<?php

namespace Doxa\Core\Libraries\Logging;

use Exception;

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
        $str = array_shift($args_arr);
        if (!empty($args_arr)) {
            $level = array_shift($args_arr);
        } else {
            $level = 1;
        }

        $log_level = config('project_options.log_level');
        if (!$log_level) {
            return false;
        }

        if (!$level) {
            $level = 1;
        }

        if ($log_level < 4) {
            if ($level > $log_level) {
                return false;
            }
        }

        $filepath = base_path('storage/logs') . '/' . $file_prefix . '_' . date('Y-m-d') . '.log';

        if (!empty($args_arr)) {
            $str = vsprintf($str, $args_arr);
        }
        if ($str === '') {
            return false;
        }

        $str = str_replace(array("\r", "\n"), ' ', $str);

        @clearstatcache();

        if (!($log_size = @filesize($filepath))) {
            $log_size = 0;
        }

        if (!($fil = @fopen($filepath, 'a'))) {
            return false;
        }

        // if( empty( $log_size ) )
        // {
        //     fputs( $fil, "        Date        |        IP       |                              METHOD                                | LINE |     Log\n" );
        //     fputs( $fil, "--------------------+-----------------+--------------------------------------------------------------------+------+-------------------------------------------------------------\n" );
        // }

        if (empty($log_size)) {
            fputs($fil, "      Date      |                           METHOD                         | LINE |     Log\n");
            fputs($fil, "----------------+----------------------------------------------------------+------+-------------------------------------------------------------\n");
        }

        if (!empty($_SERVER['SERVER_ADDR'])) {
            $request_ip = $_SERVER['SERVER_ADDR'];
        } else {
            if (!empty($_SERVER['REMOTE_ADDR'])) {
                $request_ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $request_ip = '(unknown)';
            }
        }

        $params = Clog::GetCallingParams();

        //$p = explode("\\", $params['method']);

        $p = explode("\\", $params['method']);
        //dump($p);
        //dd(array_pop($p));

        $params['method'] = array_pop($p);

        // @fputs( 
        //     $fil, 
        //     date( 'd-m-Y H:i:s' ).' | '.
        //             //(!empty( $notification_identifier )?str_pad( $notification_identifier, 15, ' ', STR_PAD_LEFT ).' | ':'').
        //             str_pad( $request_ip, 15, ' ', STR_PAD_RIGHT ).' | '.
        //             str_pad( $params['method'], 66, ' ', STR_PAD_RIGHT ).' | '.
        //             str_pad( $params['line'], 4, ' ', STR_PAD_RIGHT ).' | '.
        //               $str."\n" );

        @fputs(
            $fil,
            date('d M H:i:s') . ' | ' .
                str_pad($params['method'], 56, ' ', STR_PAD_RIGHT) . ' | ' .
                str_pad($params['line'], 4, ' ', STR_PAD_RIGHT) . ' | ' .
                $str . "\n"
        );

        @fflush($fil);
        @fclose($fil);
    }

    static function GetCallingParams()
    {
        $e = new Exception();
        $trace = $e->getTrace();
        //position 0 would be the line that called this function so we ignore it
        return array(
            'line' => $trace[1]['line'],
            'method' => $trace[2]['class'] . '::' . $trace[2]['function']
        );
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

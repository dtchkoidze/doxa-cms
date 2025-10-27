<?php

namespace Doxa\Core\Helpers;

use Doxa\Core\Libraries\Logging\Clog;

class Error
{
    protected static self|null $instance = null;

    private $errors = [];

    private $log_name = 'errors';
    private $log_level = 1;

    final private function __construct() {}

    public static function add($message, $code = 100, $data = '', $log_to = '')
    {
        if (static::$instance === null) {
            static::$instance = new static;
        }
        static::$instance->addError($message, $code, $data, $log_to);
        return static::$instance;
    }

    public static function get()
    {
        if (static::$instance === null) {
            return false;
        }
        return static::$instance->getErrors();
    }

    public static function getString()
    {
        if (static::$instance === null) {
            return '';
        }
        return static::$instance->getErrors('string', ', ');
    }

    public static function is()
    {
        if (static::$instance === null) {
            return false;
        }
        return !empty(static::$instance->errors);
    }

    private function addError($message, $code, $data, $log_to)
    {
        $this->errors[] = [
            'message' => $message,
            'code' => $code,
            'data' => $data
        ];

        Clog::write($this->log_name, $message, $this->log_level);
        if ($log_to) {
            Clog::write($log_to, $message, $this->log_level);
        }
    }

    private function getErrors($format = 'array', $delimiter = '<br>')
    {
        if ($format == 'array') {
            return $this->errors;
        }
        if ($format == 'json') {
            return json_encode($this->errors);
        }
        if ($format == 'string') {
            $errors = collect($this->errors)->pluck('message');
            return implode($delimiter, $errors->toArray());
        }
        if ($format == 'html') {
            return ''; // todo;
        }

        return $this->errors;
    }
}

<?php

namespace Doxa\Libraries\QRCode;

use Illuminate\Database\Eloquent\Collection;

class QR
{
    public static self|null $instance = null;

    protected $driver = 'SimpleSoftwareIO';

    protected $_driver;

    /**
     * Text to encode
     * @var string
     */
    protected $text = ''; 

    protected $type = '';

    /**
     * Size in pixels
     * @var int
     */
    protected $size = 200;

    /**
     * Background color in HEX
     * @var string
     */
    protected $bgColor = '';

    /**
     * Foreground color in HEX
     * @var string
     */
    protected $fgColor = '#000000';

    /**
     * Margin in QR Code min px block
     * @var int
     */
    protected $margin = 0;

    protected $params = [
        'size',
        'bgColor',
        'fgColor',
        'margin',
        'text',
    ];

    protected $validator_errors = [];

    private final function  __construct() 
    {
        //dump( __CLASS__ . " initializes only once\n" );
    }

    public static function init($params, $reset = false)
    {
        if(!isset(self::$instance) || $reset){ 
            self::$instance = new QR();
            self::$instance->_init($params);
        }
        return self::$instance;
    }

    private function _init($params)
    {
        // dump($params);
        foreach($params as $key => $value){
            $validator_method = 'validate_' . $key; 
            if(method_exists($this, $validator_method)){
                $this->{$validator_method}($value);
            } else {
                $this->{$key} = $value;
            }
        }
        if(empty($params['text'])){
            $this->validator_errors['text'] = 'Text is required';
        }

        if(!empty($params['type'])){
            // todo
        }

        $driver_class = 'Doxa\\Libraries\\QRCode\\drivers\\' . $this->driver;

        $this->_driver = new $driver_class($this);
    }

    public static function validate()
    {
        if(self::$instance === null){
            dd('Class not initialized. use static method init($params)');
        }
        return self::$instance->_validate();
    }

    private function _validate()
    {
        if($this->validator_errors){
            return $this->validator_errors;
        }
        return true;
    }

    public static function isValid()
    {
        if(self::$instance === null){
            dd('Class not initialized. use static method init($params)');
        }

        return self::$instance->_validate() === true;
    }

    private function getParamsArray()
    {
        $params_array = [];
        foreach($this->params as $param){
            $params_array[$param] = $this->{$param};
        }
        return $params_array;
    }

    public static function generate($format = '', $filepath = '')
    {
        if(self::$instance === null){
            dd('Class not initialized. use static method init($params)');
        }

        
        return self::$instance->_driver->generate(self::$instance->getParamsArray(), $format, $filepath);
    }

    private function validate_text($value)
    {
        if(!trim($value)){
            $this->validator_errors['text'] = 'Text is required';
            return false;
        }
        $this->text = $value;
        return true;
    }

    private function validate_size($value)
    {
        $value = (int) $value;

        if(!$value){
            $this->validator_errors['size'] = 'Size is required';
            return false;
        }

        $this->size = $value;

        return true;
    }

    private function validate_bgColor($value)
    {
        if(!trim($value)){
            return true;
        }
        if( !preg_match('/^#[a-f0-9]{6}$/i', $value) ){
            $this->validator_errors['bgColor'] = "The background color code is not valid";
            return false;
        }

        $this->bgColor = $value;

        return true;
    }

    private function validate_fgColor($value)
    {
        if(!trim($value)){
            return true;
        }
        if( !preg_match('/^#[a-f0-9]{6}$/i', $value) ){
            $this->validator_errors['fgColor'] = "The color code is not valid";
            return false;
        }

        $this->fgColor = $value;

        return true;
    }

    private function validate_margin($value)
    {
        $value = (int) $value;

        if (!$value) {
            $this->validator_errors['margin'] = 'Margin is required';
            return false;
        }

        $this->margin = $value;

        return true;

    }








    public function init2(
        string $text,
        int $size,
        array $bgColor,
        array $fgColor,
        int $margin,
    ): void
    {
        # code...
    }

    public function set()
    {

    }
}

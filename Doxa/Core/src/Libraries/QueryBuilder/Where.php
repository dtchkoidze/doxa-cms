<?php

namespace Doxa\Core\Libraries\QueryBuilder;

use Doxa\Core\Helpers\Error;
use Doxa\Core\Helpers\Logging\Clog;

class Where
{
    protected static self|null $instance = null;


    public $_data = [];

    protected $config;

    public string $key;

    public string $operand;

    public mixed $value;

    private string $_key;

    private string $_operand;

    private mixed $_value;

    private string $request_string = '';

    public $by_table = '';

    public function __construct($_data, $config)
    {
        $this->_data = $_data;
        $this->config = $config;
        //dump('$_data: ',$_data);
        if($this->check()){
            $this->initialize();
            $this->resolve();
        }
    }

    /**
     * Check  if where argument is valid
     *
     * @return bool
     */
    private function check(): bool
    {
        if(!isset($this->_data[1])){
            Error::add('Wrong Where::check initialize argument: '.json_encode($this->_data).' required sequential array with 2 members minimum: key and value');
            return false;
        }
        return true;
    }

    /**
     * Create _key, _operand, _value initial properties
     *
     * @return void
     */
    private function initialize()
    {
        $this->_key = $this->_data[0];
        if(array_key_exists(2, $this->_data)){
        //if(isset($this->_data[2])){
            $this->_value = $this->_data[2];
            $this->_operand = $this->_data[1];
        } else {
            $this->_value = $this->_data[1];
            $this->_operand = '';
        }
    }

    /**
     * Check key, operand, value
     *
     * @return void
     */
    private function resolve()
    {
        // -------- KEY
        // try to separate key and operand if key contains operand
        // example: 'id > ' => 1, 'text <> ' => ''
        $separated = $this->trySeparateOperand($this->_key);
        if(!$separated['key']){
            Error::add('Key crached after operand separate in where, request: '.json_encode($this->_data));
            return false;
        }

        // separate operations return key anywhere (except error case)
        $key = $separated['key'];
        // so we can check if column exist in table and add an alias prefix
        $this->key = $this->resolveWhereKey($key);
        if(!$this->key){
            Error::add('Key "'. $separated['key'].'" not resolved in where, request: '.json_encode($this->_data));
            return false;
        }

        // -------- OPERAND
        $this->operand = '=';
        if($this->_operand){
            $this->operand = $this->resolveOperand($this->_operand);
            if(!$this->operand){
                Error::add('Operand ('.$this->_operand.') not resolved in where, request: '.json_encode($this->_data));
                return false;
            }
        } else {
            if($separated['operand']){
                $this->operand = $separated['operand'];
            }
        }

        // -------- VALUE
        $this->value = $this->resolveWhereVal($this->_value);

        $this->request_string = "`" . $this->key . "` " . $this->operand . " " . $this->value;

        //dump('$this->request_string: '.$this->request_string);
    }

    /**
     * Try to separate key and operand if key contains operand
     *
     * @param string $key
     * @return array
     */
    private function trySeparateOperand($key)
    {
        $operand = false;
        $a = explode(' ', $key);
        $key = trim($a[0]);
        if(!empty($a[1])){
            $operand = $this->resolveOperand($a[1]);
            if(!$operand){
                Error::add('Operand ('.$a[1].') given via separating key ('.$key.') is wrong');
                $key = false;
            }
        }
        return ['key' => $key, 'operand' => $operand];
    }

    /**
     * Resolve where key: check is column exists and add base or variation prefix
     *
     * @param string $key
     * @return string|false
     */
    private function resolveWhereKey($key)
    {
        $this->by_table = 'base';

        $p = explode('.', $key);
        if(sizeof($p) > 1){
            $prefix = trim($p[0]);
            $key = trim($p[1]);
            if(in_array($prefix, $this->config->variation_prefixes)){
                $this->by_table = 'variation';
            }
        }

        if($this->config->scheme->isFieldExists($key, $this->by_table)){
            return $key;
        } else {
            Error::add('Wrong key in where: '. $key .', not exists in base table ('.$this->config->table.'), provided key: '.$key);
            return false;
        }

    }

    /**
     * Returns WHERE value with correct type
     *
     * @param mixed $val
     * @return mixedt
     */
    private function resolveWhereVal($val)
    {
        if($this->operand == 'LIKE'){
            return "%$val%";
        }
        if($this->operand == 'IN' || $this->operand == 'NOT IN'){
            if(is_array($val)){
                $val = implode(',', $val);
            }
            return '('.$val.')';
        }
        // if($this->_key == 'created_at'){
        //     dump($val);
        // }
        if(is_int($val) || is_float($val) || (int)$val == $val){
            return $val;
        } else {
            return "'$val'";
        }
    }

    /**
     * Filters operand
     *
     * @param string $operand
     * @return string|false
     */
    private function resolveOperand($operand)
    {
        $operand = strtoupper(trim($operand));
        return in_array($operand, $this->config->operands) ? $operand : false;
    }

    public function getRequestString()
    {
        return $this->request_string;
    }

    public function getByTable()
    {
        return $this->by_table;
    }

}

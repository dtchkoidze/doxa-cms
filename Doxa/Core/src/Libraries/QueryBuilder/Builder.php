<?php

namespace Doxa\Core\Libraries\QueryBuilder;

use Doxa\Core\Helpers\Error;


class Builder
{
    public $config;

    private $wheres = [];

    private $select_build = [];

    private $where_build = [];

    private $from_build = [];

    private $sql = '';

    public function __construct(&$config)
    {
        $this->config = $config;
    }

    private function reset()
    {
        $this->select_build = [];
    }

    public function createFromBuild()
    {
        $this->from_build[] = $this->config->table;
        if($this->config->variations_table){
            $this->from_build[] = $this->config->variations_table;
        }
    }

    public function createSelectBuild()
    {
        foreach($this->config->scheme->getTableFieldsList() as $key => $params){
            $this->select_build[] = $this->config->table . "." . $key . " as '" . 'b__' . $key . "'";
        }
        if($this->config->variations_table){
            foreach($this->config->scheme->getVariationsTableFieldsList() as $key => $params){
                $this->select_build[] = $this->config->variations_table . "." . $key . " as '" . 'v__'.$key . "'";
            }
        }
    }

    public function where()
    {
        $args = func_get_args();
        // dump('func_get_args: ',$args);
        // dump('sizeof($args): '.sizeof($args));

        if(sizeof($args) == 1){
            // if 1 argiment - it's must be an array
            if(is_array($args[0])){

                foreach($args[0] as $key =>$val){
                    
                    if(is_int($key)){
                        // it's a sequential member, the value mustr be array
                        // examples: 
                        // 0 => ['id', 8]
                        // 2 => ['date', '>', '12-12-2012']
                        if(!is_array($val)){
                            Error::add('Wrong where: sequential member must be array ' . gettype($val).' given');
                            continue;
                        }
                        $this->wheres[] = new Where($val, $this->config);
                    } else {
                        // it's a key => value pair 
                        // examples: 
                        // 'id' => 8
                        // 'id >' => 2
                        $this->wheres[] = new Where([$key, $val], $this->config);
                    }
                    
                }

            } else {
                Error::add('Wrong where: single argument must be array ' . gettype($args[0]).' given');
            }
            
        } else {
            if(sizeof($args) == 2){
                $this->wheres[] = new Where([$args[0], $args[1]], $this->config);
            } else {
                $this->wheres[] = new Where([$args[0], $args[1], $args[2]], $this->config);
            }
        }

        return $this;
    }

    public function getWhereBuild()
    {
        foreach($this->wheres as $whereObj){
            if($str = $whereObj->getRequestString()){
                $this->where_build[] = $str;
            }
        }
        if($this->config->variations_table){
            $this->addVariationJoinWheres();
        }
        
        //return $this->where_build;
    }

    public function addVariationJoinWheres()
    {
        $this->where_build[] = $this->config->variations_table.'.src_id = '.$this->config->table.'.id'; 
    }

    public function getSQL()
    {
        $this->resolveWith();

        $this->createFromBuild();
        $this->getWhereBuild();
        $this->createSelectBuild();

        //dump($this->from_build);
        //dump($this->where_build);

        $sql = 'SELECT ' . implode(', ', $this->select_build) . ' FROM ' . implode(', ', $this->from_build).' WHERE ' . implode(' AND ', $this->where_build);
        dump($sql);
        return $sql;
    }

    public function resolveWith()
    {
        if(!empty($this->config->with)){
            foreach($this->config->with as $with){
                dump($with);
                //$this->config->scheme->addWith($with);
                if($with == 'images'){
                    $this->resolveImages();
                    // if($this->config->scheme->isImagesExists($with)){
                    //     $this->resolveImages();
                    // }
                }
            }
        }
    }

    public function resolveImages()
    {
        $select = [
            'images.src_key as images__src_key',
            'images.path as images__path',
            'images.src_type as images__src_type',
            'images.src_id as images__src_id',
        ];
        $this->select_build = array_merge($this->select_build, $select);
        dump($this->select_build);

        $this->from_build[] = 'images';

        $this->where_build[] = "images.src_type = '".$this->config->table."'"; 
        $this->where_build[] = 'images.src_id = '.$this->config->table.'.id'; 
    }

}



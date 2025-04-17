<?php

namespace Doxa\Core\Libraries\Package;

class Relation
{
    protected $rel;

    protected $doxa_connects_aliases = [

        'common',
        'doxa',
    ];

    protected $default_relation_connects_table = 'doxa_connects';

    protected $default_image_connects_table = 'image_connects';

    protected $default_image_src_table = 'images';

    const MODE_WITHOUT_CONNECT_TABLE = 'without_connect';

    const MODE_WITH_CONNECT_TABLE = 'with_connect';

    const MODE_WITH_CUSTOM_CONNECT_TABLE = 'with_custom_connect';

    const MODE_WITH_SRC_TABLE = 'with_src_table';

    const IMAGE_PATH_ALIASES = [
        'path'
    ];

    protected $connect_table = null;

    protected $src_table = '';

    protected $columns = [];

    protected $mode = '';

    protected $errors = [];

    protected string $desc = '';


    public function __construct($rel)
    {
        $this->rel = $rel;
        $this->_getMode();
        $this->_getConnectTable();
        $this->_getConnectColumns();
        $this->_checkRelation();
    }

    private function _getMode()
    {
        if(isset($this->rel['relation']['connect_table'])){
            $this->mode = self::MODE_WITH_CONNECT_TABLE;
            if(!in_array($this->rel['relation']['connect_table'], $this->doxa_connects_aliases)){
                $this->mode = self::MODE_WITH_CUSTOM_CONNECT_TABLE;
            }
        } else {
            if(!empty($this->rel['relation']['with_src_table'])){
                $this->mode = self::MODE_WITH_SRC_TABLE;
            } else {
                $this->mode = self::MODE_WITHOUT_CONNECT_TABLE;
            }
        }
    
    }

    private function _getConnectTable()
    {
        switch($this->mode){
            case self::MODE_WITH_CUSTOM_CONNECT_TABLE:
                $this->connect_table = $this->rel['relation']['connect_table'];
                break;
            case self::MODE_WITH_CONNECT_TABLE:
                if($this->isImage()){
                    $this->connect_table = $this->default_image_connects_table;
                    if(empty($this->rel['relation']['src_table'])){
                        $this->src_table = $this->default_image_src_table;
                    }
                }
                if($this->isRelated()){
                    $this->connect_table = $this->default_relation_connects_table;
                }
                break;
            default:
                $this->connect_table = null;
                break;
        }
    }

    private function _getConnectColumns()
    {
        $data = [
            'src_id' => ['default' => 'src_id', 'required' => true],
            'src_type' => ['default' => 'src_type', 'required' => false],
            'src_key' => ['default' => 'src_key', 'required' => false],
            'src_package' => ['default' => 'src_package', 'required' => false],
            'connect_id' => ['default' => 'connect_id', 'required' => false],
            'connect_type' => ['default' => 'connect_type', 'required' => false],
            'connect_key' => ['default' => 'connect_key', 'required' => false],
            'connect_package' => ['default' => 'connect_package', 'required' => false],
        ];

        $default_aliases = [
            'default',
            'true',
        ];

        foreach($data as $column => $params){
            $this->columns[$column.'_column'] = null;
            if($this->mode == self::MODE_WITHOUT_CONNECT_TABLE){
                continue;
            }

            if($this->mode == self::MODE_WITH_CONNECT_TABLE){
                $this->columns[$column.'_column'] = $params['default'];
                continue;
            } 

            if($this->mode == self::MODE_WITH_CUSTOM_CONNECT_TABLE || $this->mode == self::MODE_WITH_SRC_TABLE){
                if(isset($this->rel['relation']['columns'])){
                    /**
                     * Example:
                     * columns' => [
                     *       'connect_id', // this will be default value = connect_id
                     *       'connect_type', // the same as above
                     *       'connect_key' => 'src_key', // it's a custom column name for conect_key
                     *       'src_id' => 'category_id', // it's a custom column name for src_id
                     *   ],
                     */
                    if(isset($this->rel['relation']['columns'][$column])){
                        $this->columns[$column.'_column'] = $this->rel['relation']['columns'][$column];
                    } else {
                        foreach($this->rel['relation']['columns'] as $ckey => $cvalue){
                            if(is_numeric($ckey)){
                                if($cvalue == $column){
                                    $this->columns[$column.'_column'] = $params['default'];
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _checkRelation()
    {
        if($this->mode == self::MODE_WITHOUT_CONNECT_TABLE){
            if(!$this->srcPackage()){
                $this->errors[] = 'You must provide a src package if you building relation without connect table.';
            }
        }
    }    


    public function get()
    {
        return $this->rel;
    }

    public function mode()
    {
        return $this->mode;
    }

    public function type()
    {
        return $this->rel['type'];
    }

    public function isRelated()
    {
        return $this->rel['type'] == 'related';
    }

    public function isImage()
    {
        return $this->rel['type'] == 'images';
    }

    public function connectTable()
    {
        return $this->connect_table;
    }

    public function srcIdColumn()
    {
        return $this->columns['src_id_column'];
    }

    public function srcTypeColumn()
    {
        return $this->columns['src_type_column'];
    }

    public function srcKey()
    {
        return !empty($this->rel['relation']['src_key']) ? $this->rel['relation']['src_key'] : null;
    }

    public function srcKeyColumn()
    {
        return $this->columns['src_key_column'];
    }

    public function srcPackageColumn()
    {
        return $this->columns['src_package_column'];
    }


    public function connectIdColumn()
    {
        // if(!isset($this->columns['connect_id_column'])){
        //     dd($this->mode, $this->rel, $this->columns);
        // }
        return $this->columns['connect_id_column'];
    }

    public function connectTypeColumn()
    {
        return $this->columns['connect_type_column'];
    }

    public function connectKeyColumn()
    {
        return $this->columns['connect_key_column'];
    }

    public function connectPackageColumn()
    {
        return $this->columns['connect_package_column'];
    }

    public function connectKey()
    {
        return $this->rel['key'];
    }

    public function customListMethod()
    {
        return !empty($this->rel['relation']['list_method']) ? $this->rel['relation']['list_method'] : false;
    }

    public function listFields()
    {
        return !empty($this->rel['relation']['list_fields']) ? $this->rel['relation']['list_fields'] : false;
    }
    
    
    public function isBase()
    {
        return $this->rel['table'] == 'base';
    }

    public function isVariation()
    {
        return $this->rel['table'] == 'variation';
    }

    public function srcPackage()
    {
        return !empty($this->rel['relation']['src_package']) ? $this->rel['relation']['src_package'] : null;
    }

    public function srcTable()
    {
        if(!empty($this->rel['relation']['with_src_table'])){
            return $this->rel['relation']['with_src_table'];
        }
        if($this->src_table){
            return $this->src_table;
        }
        if(!empty($this->rel['relation']['src_table'])){
            return $this->rel['relation']['src_table'];
        }
        return null;
    }

    public function configError()
    {
        return !empty($this->errors) ? implode('; ',$this->errors) : false;
    }

    public function desc()
    {
        return $this->desc;
    }

    public function info()
    {
        //dd(__METHOD__, $this->rel);
        $info = [
            'type' => 'related',
            'key' => $this->rel['key'],
            'mode' => $this->mode,

            'src_package' => $this->srcPackage(),
            'src_table' => $this->srcTable(),
            'src_key' => $this->srcKey(),
            
            'base|variation' => $this->rel['table'],
            'options' => $this->rel['options'],

            'connect_table' => $this->connectTable(),

            'columns' => $this->columns,

            'COLUMN connect_id' => $this->connectIdColumn(),
            'COLUMN connect_type' => $this->connectTypeColumn(),
            'COLUMN connect_key' => $this->connectKeyColumn(),
            'COLUMN connect_package' => $this->connectPackageColumn(),

            'COLUMN src_id' => $this->srcIdColumn(),
            'COLUMN src_type' => $this->srcTypeColumn(),
            'COLUMN src_key' => $this->srcKeyColumn(),

            'error' => $this->errors,

        ];

        //dump($this->errors);

        dump($info);
    }


}
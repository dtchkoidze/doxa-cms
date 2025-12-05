<?php

namespace Doxa\Core\Libraries\Package;

class Scheme
{
    private $scheme;

    private string $table_name;

    private string $variations_table_name = '';

    private $table_fields_list = [];

    private $variations_table_fields_list = [];

    private $table_scheme = [];

    private $variation_table_scheme = [];

    public function __construct($scheme)
    {
        if(!$scheme){
            return;
        }

        $this->table_scheme = $scheme['table'];
        if(isset($scheme['variations_table'])){
            $this->variation_table_scheme = $scheme['variations_table'];
        }

        $this->table_name = $this->table_scheme['name'];
        if(!empty($this->variation_table_scheme)){
            $this->variations_table_name = $this->variation_table_scheme['name'];
        }

        $this->_getTableFieldsList();
        if (!empty($this->variation_table_scheme)) {
            $this->_getVariationsTableFieldsList();
        }

    }

    public function getRequiredTableFields($tb)
    {
        if($tb == 'base'){
            return [
                'id' => [
                    'as' => 'base.id',
                    'required' => true,
                ],
                'active' => [
                    'as' => 'base.active',
                    'required' => true,
                ],
            ];
        }
        if($tb == 'variation'){
            return [
                'id' => [
                    'as' => 'variation.id',
                    'required' => true,
                ],
                'active' => [
                    'required' => true,
                ],
                'channel_id' => [
                    'required' => true,
                ],
                'locale_id' => [
                    'required' => true,
                ],
                'src_id' => [
                    'required' => true,
                ],
            ];
        }
    }

    private function _getTableFieldsList()
    {
        $stop = ['related', 'images'];

        $this->table_fields_list = $this->getRequiredTableFields('base');

        if(empty($this->table_scheme['fields'])){
            return;
        }
        foreach($this->table_scheme['fields'] as $key => $val){
            if(is_numeric($key)){
                $key = $val;
            } 
            if(!in_array($key, $stop)){
                $this->table_fields_list[$key] = [
                    'as' => 'base.'.$key,
                ];
            }
        }
    }

    private function _getVariationsTableFieldsList()
    {
        $stop = ['related', 'images'];

        $this->variations_table_fields_list = $this->getRequiredTableFields('variation');

        //dd($this->variations_table_fields_list);

        foreach($this->variation_table_scheme['fields'] as $key => $val){
            if(is_numeric($key)){
                $key = $val;
            } 
            if(!in_array($key, $stop)){
                if(is_array($key)){
                    if(isset($key['from']) && isset($key['to'])){
                        for($i = $key['from']; $i <= $key['to']; $i++){
                            $this->variations_table_fields_list[$key['prefix'].$i] = [
                                'as' => 'variation.'.$key['prefix'].$i,
                            ];
                        }
                    }
                } else {
                    $this->variations_table_fields_list[$key] = [
                        'as' => 'variation.'.$key,
                    ];
                }
                
            }
        }
    }

    public function getTableFieldsList()
    {
        return $this->table_fields_list;
    }

    public function getVariationsTableFieldsList()
    {
        return $this->variations_table_fields_list;
    }


    public function isFieldExists($field, $type = '')
    {
        if(!$type || $type == 'base'){
            return isset($this->table_fields_list[$field]);
        }
        if($type == 'variation' || $type == 'v'){
            return isset($this->variations_table_fields_list[$field]);
        }
        return false;
    }

    public function getTable()
    {
        return $this->table_name;
    }

    public function getVariationsTable()
    {
        return $this->variations_table_name;
    }

    public function getKeyField()
    {


        if(empty($this->config['scheme']['table'])){
            return $this->handleExeption('scheme_not_provided');
        }

        if(!empty($this->config['scheme']['table']['fields']['url_key'])){
            return 'url_key';
        }

        if(!empty($this->config['scheme']['table']['fields']['key'])){
            return 'key';
        }

        return false;
    }

    public function isImagesExists($type = '')
    {
        if(!$type || $type == 'base'){
            return isset($this->table_scheme['images']);
        }
        if($type == 'variation' || $type == 'v'){
            return isset($this->variation_table_scheme['images']);
        }
        return false;
    }

    public function getRelation($name, $options = [], $type = '')
    {
        $output = [];

        $a = explode('.', $name);
        if(!empty($a[1])){
            $name = $a[1];
            if($a[0] == $this->getTable()){
                $type = 'base';
            } else {
                $type = 'variation';
            }
        }

        $type == 'base' && $tbs = ['table' => 'base'];
        $type == 'variation' && $tbs = ['variation_table' => 'variation'];
        !$type && $tbs = ['table' => 'base', 'variation_table' => 'variation'];

        foreach($tbs as $tb => $table){
            if (!isset($this->{$tb . '_scheme'}['fields'])) {
                continue;
            }
            $fields = $this->{$tb.'_scheme'}['fields'];

            if($name == 'related'){
                foreach($fields as $key => $params){
                    if(isset($params['type']) && $params['type'] == 'related'){
                        $a = [
                            'type' => 'related',
                            'table' => $table,
                            'key' => $key,
                            'relation' => $params['relation'],
                            'options' => $options
                        ];
                        $output[] = new Relation($a);
                    }
                }   
            } else if($name == 'images'){
                foreach($fields as $key => $params){
                    if(isset($params['type']) && $params['type'] == 'images'){
                        $a = [
                            'type' => 'images',
                            'table' => $table,
                            'key' => $key,
                            'relation' => isset($params['relation']) ? $params['relation'] : [],
                            'options' => $options
                        ];
                        $output[] = new Relation($a);
                    }
                }    
            } else {
                foreach($fields as $key => $params){
                    if($key == $name){
                        if(!isset($params['type'])){
                            continue;
                        }
                        $a = [
                            'type' => $params['type'],
                            'table' => $table,
                            'key' => $key,
                            'relation' => isset($params['relation']) ? $params['relation'] : [],
                            'options' => $options
                        ];
                        $output[] = new Relation($a);
                    }
                }
            }
        } 
        
        return $output;
    }

    // public function getRelationInfo($name, $options = [], $type = '')
    // {
    //     $output = [];

    //     $type == 'base' && $tbs = ['table' => 'base'];
    //     $type == 'variation' && $tbs = ['variation_table' => 'variation'];
    //     !$type && $tbs = ['table' => 'base', 'variation_table' => 'variation'];

    //     foreach($tbs as $tb => $table){
    //         if (!isset($this->{$tb . '_scheme'}['fields'])) {
    //             continue;
    //         }
    //         $fields = $this->{$tb.'_scheme'}['fields'];

    //         if($name == 'related'){
    //             foreach($fields as $key => $params){
    //                 if(isset($params['type']) && $params['type'] == 'related'){
    //                     $a = [
    //                         'type' => 'related',
    //                         'table' => $table,
    //                         'key' => $key,
    //                         'relation' => $params['relation'],
    //                         'options' => $options
    //                     ];
    //                     $output[] = new Relation($a);
    //                 }
    //             }   
    //         } else if($name == 'images'){
    //             foreach($fields as $key => $params){
    //                 if(isset($params['type']) && $params['type'] == 'images'){
    //                     $a = [
    //                         'type' => 'images',
    //                         'table' => $table,
    //                         'key' => $key,
    //                         'relation' => isset($params['relation']) ? $params['relation'] : [],
    //                         'options' => $options
    //                     ];
    //                     $output[] = new Relation($a);
    //                 }
    //             }    
    //         } else {
    //             foreach($fields as $key => $params){
    //                 if($key == $name){
    //                     if(!isset($params['type'])){
    //                         continue;
    //                     }
    //                     $a = [
    //                         'type' => $params['type'],
    //                         'table' => $table,
    //                         'key' => $key,
    //                         'relation' => isset($params['relation']) ? $params['relation'] : [],
    //                         'options' => $options
    //                     ];
    //                     $output[] = new Relation($a);
    //                 }
    //             }
    //         }
    //     } 
        
    //     return $output;
    // }

    // public function getRelationInfo_OLD($name, $options = [], $type = '')
    // {
    //     //dump('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> getRelationsInfo: '.$name, $options);
    //     $output = [];

    //     $type == 'base' && $tbs = ['table' => 'base'];
    //     $type == 'variation' && $tbs = ['variation_table' => 'variation'];
    //     !$type && $tbs = ['table' => 'base', 'variation_table' => 'variation'];

    //     foreach($tbs as $tb => $table){
    //         if (!isset($this->{$tb . '_scheme'}['fields'])) {
    //             continue;
    //         }
    //         $fields = $this->{$tb.'_scheme'}['fields'];
    //         dd($fields);

    //         if($name == 'related'){
    //             //dump('$key == related');
    //             if(isset($fields['related'])){
    //                 //dump('isset');
    //                 foreach($fields['related'] as $key => $params){
    //                     $a = [
    //                         'rel' => 'related',
    //                         'table' => $table,
    //                         'key' => $key,
    //                         'data' => $params,
    //                         'options' => $options
    //                     ];
    //                     //$output[] = $a;
    //                     $output[] = new Relation($a);
    //                 }
    //             }
    //         } else if($name == 'images'){
    //             if(isset($fields['images'])){
    //                 $a = [
    //                     'rel' => 'images',
    //                     'table' => $table,
    //                     'data' => $fields['images'],
    //                     'options' => $options
    //                 ];
    //                 //$output[] = $a;
    //                 $output[] = new Relation($a);
    //             }
    //         } else {
    //             if(isset($fields['related'][$name])){

    //                 $a = [
    //                     'rel' => 'related',
    //                     'table' => $table,
    //                     'key' => $name,
    //                     'data' => $fields['related'][$name],
    //                     'options' => $options
    //                 ];
    //                 //$output[] = $a;
    //                 $output[] = new Relation($a);
    //             } 
    //             if(isset($fields['images'][$name])){
    //                 $a = [
    //                     'rel' => 'images',
    //                     'table' => $table,
    //                     'data' => $fields['images'][$name],
    //                     'options' => $options
    //                 ];
    //                 //$output[] = $a;
    //                 $output[] = new Relation($a);
    //             }
    //         }
    //     }
    //     //dump('>>>>>>>>>>>>>>>-- END -->>>>>>>>>>>>>>>>>>>>> getRelationsInfo: '.$name);
    //     return $output;
    // }  
    
    // public function getRelationInfoByKey($key, $type = '')
    // {
    //     $r = $this->getRelationInfo($key, type: $type);
    //     return !empty($r) ? $r[0] : false;
    // }

    public function getRelationByKey($key): bool|Relation
    {
        $relation = $this->getRelation($key);
        return !empty($relation) ? $relation[0] : false;
    }

    public function tryeGetImageOrRelated($str){

        //dump('$str: '.$str);

        $output = [];

        $tbs = [
            'table' => 'base', 
            'variation_table' => 'variation',
        ];

        foreach($tbs as $tb => $table){
            if(isset($this->{$tb.'_scheme'}['fields'][$str])){
                foreach($this->{$tb.'_scheme'}['fields'][$str] as $key => $params){
                    $output[] = [
                        'key' => $key,
                        'table' => $table,
                        'rel' => $str,
                        'params' => $params,
                    ];
                }
                
            }
        }

        return $output;
    }

    public function isVariationHasPosition()
    {
        return !empty($this->variation_table_scheme['fields']['position']);
    }

    public function isVariationHasAdminId()
    {
        return !empty($this->variation_table_scheme['fields']['admin_id']);
    }

    public function isVariationHasCreatedAt()
    {
        return !empty($this->variation_table_scheme['fields']['created_at']);
    }

    public function isVariationHasUpdatedAt()
    {
        return !empty($this->variation_table_scheme['fields']['updated_at']);
    }


}

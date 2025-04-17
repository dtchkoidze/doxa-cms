<?php

namespace Doxa\Core\Libraries\ModuleManager\Relation\modes;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Doxa\Core\Libraries\ModuleManager\Manager;
use Doxa\Core\Libraries\ModuleManager\Relation\RelationBase;

class WithoutConnect extends RelationBase
{
    protected function _attachRelatedRecords()
    {
        $related_ids = [];
        foreach($this->records as $record){
            if($record->{$this->key}){
                $a = explode(',', $record->{$this->key});
                $record->{$this->key} = $a;
                foreach($a as $identificator){
                    if(!in_array($identificator, $related_ids)){
                        $related_ids[] = is_numeric($identificator) ? trim($identificator) : trim($identificator);
                    }
                }
            }
        }

        //---------------
        Manager::$test_mode && dump([
            'stage' => 'GOT related ids',
            '$related_ids: ' => $related_ids,
        ]);
        //---------------

        if((sizeof($related_ids) == 1 && $related_ids[0] == 0) || empty($related_ids)) {
            foreach($this->records as $record){
                $record->{$this->key} = [];
            }
            return;
        }

        $this->getRelatedRecordsByIds($related_ids); 

        //---------------
        Manager::$test_mode && dump([
            'stage' => 'GOT related records',
            '$related_records' => $this->related_records,
        ]);
        //---------------

        // OLD, see NEW
        if($this->related_records){
            foreach($this->records as $record){
                if(empty($record->{$this->key})){
                    $record->{$this->key} = [];
                } else {
                    $a = [];
                    foreach($record->{$this->key} as $identificator){
                        //dump($identificator);
                        if(isset($this->related_records[$identificator])){
                            $a[] = $this->related_records[$identificator];
                        }
                    };
                    $record->{$this->key} = $a;
                }
            }
        }

        // NEW, we believe withoutConnect includes only 1 related record
        // 26.02.2025 Why I returned to this? Most of relations are on to one, so we need to return ONE record relation and not array,
        // example: message status, if we return an array we always must get related as $message->status[0]->name. It's not a good idea.
        // better to return $message->status->name. By reson I don't remember I had to return to old method: return related as array.
        // And then swithc to this again, cause a huge code use related without array like $message->status->name.
        // When next time we'll face this problem, we must find a solution to return related as array or as object of single record by situation.
        // if($this->related_records){
        //     //dump('$this->related_records', $this->related_records);
        //     foreach($this->records as $record){
        //         if(empty(($record->{$this->key}))){
        //             $record->{$this->key} = '';
        //         } else {
        //             //dump($record->{$this->key});
        //             foreach($record->{$this->key} as $identificator){
        //                 //dd($identificator);
        //                 if(isset($this->related_records[$identificator])){
        //                     //dump($identificator, $this->related_records[$identificator]);
        //                     $record->{$this->key} = $this->related_records[$identificator];
        //                 } else {
        //                     $record->{$this->key} = [];
        //                 }
        //             };
        //         }
                
        //     }
        // }

    }

    protected function _saveRelatedRecords()
    {
        if(empty($this->data)){
            DB::table($this->main_table)->where('id', $this->id)->update([$this->key => '']);
            return;
        }

        if($this->relation->isImage()){
            $this->saveRelatedImages();
        } else {
            $this->saveRelateds();
        }
    }

    public function getConnectedRecordsIndex($connected_ids)
    {
        $this->getRelatedRecordsByIds($connected_ids); 
        if(!empty($this->related_records)){
            $connected_records = collect($this->related_records)->keyBy('id');
            return $connected_records;
        }
        return [];
        
    }   

    protected function saveRelateds()
    {
        $identificator_column = $this->relation->srcKey() ?? 'id';
        $a = [];
        foreach($this->data as $rec){
            $a[] = $rec[$identificator_column];
        }
        DB::table($this->main_table)->where('id', $this->id)->update([$this->key => implode(',', $a)]);
    }

    protected function saveRelatedImages()
    {
        $ids = $this->saveRelatedImagesWithImageTable();
        
        $val = empty($ids) ? '' : implode(',', $ids);
        DB::table($this->main_table)->where('id', $this->id)->update([$this->key => $val]); 
    }

    public function checkRelationConfiguration()
    {
        // check if key field exists in main table
        if(!Schema::hasColumn($this->main_table, $this->key)){
            $this->errors[$this->key][] = 'Relation key field ('.$this->key.') doesn\'t exist in table '.$this->main_table;
        }

        if($this->relation->isImage()){ 
            if(!$this->relation->srcTable()){
                $this->errors[$this->key][] = 'Relation table not specified for image type relation ('.$this->key.'). It\'s a table where images are stored. You must specify it in relation[src_table] parameter of the '.$this->key.' scheme relation block';
            } else {
                if(!Schema::hasTable($this->relation->srcTable())){
                    $this->errors[$this->key][] = 'Relation table ('.$this->relation->srcTable().') doesn\'t exist';
                } 
            }
            
        } else {
            if(!$this->relation->srcTable() && !$this->relation->srcPackage()){
                $this->errors[$this->key][] = 'Not specified src_package or src_table in relation section of relation for '.$this->key.'. 
                    So the system can\'t find table or packege which must be used as related records source. You must specify it in relation[src_table] or relation[src_package] parameter of the '.$this->key.' scheme relation block'; 
            } else {
                if($this->relation->srcTable()){
                    if(!Schema::hasTable($this->relation->srcTable())){
                        $this->errors[$this->key][] = 'Relation table ('.$this->relation->srcTable().') doesn\'t exist';
                    } 
                } else {
                    $config = config('doxa.package.' . $this->relation->srcPackage());
                    if(!$config){
                        $this->errors[$this->key][] = 'Relation package ('.$this->relation->srcPackage().') doesn\'t exist';
                    }
                }
            }
        }

    }              

}

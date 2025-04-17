<?php

namespace Doxa\Core\Libraries\ModuleManager\Relation\modes;

use Illuminate\Support\Facades\DB;
use Doxa\Core\Libraries\ModuleManager\Manager;
use Doxa\Core\Libraries\ModuleManager\Relation\RelationBase;

class WithConnect extends RelationBase
{
    protected function _attachRelatedRecords()
    {
        $ids = collect($this->records)->pluck('id')->toArray();

        

        $connected_index = $this->getConnectedRecordsIndex($ids);

        foreach($this->records as $record){
            if(isset($connected_index[$record->id])){
                $record->{$this->relation->connectKey()} = $connected_index[$record->id];
            } else {
                $record->{$this->relation->connectKey()} = [];
            }
        }

    }

    public function getConnectedRecordsIndex($connected_ids)
    {
        $where = [];

        // filter by ids
        $where[] = $this->relation->connectIdColumn() . "  IN (" . implode(',', $connected_ids) . ")";

        if($this->relation->connectTypeColumn()){
            
            $a = $this->relation->connectTypeColumn() . " = '" . $this->main_table . "'"; 
            $where[] = $a;

            //---------------
            //Manager::$test_mode && dump(['connectTypeColumn exists - '.$this->relation->connectTypeColumn(), 'adding where: ' => $a, 'summary $where: ' => $where]);
            //---------------
        }
        
        if($this->relation->connectKeyColumn()){
            $a = $this->relation->connectKeyColumn() . " = '" . $this->key . "'"; 
            $where[] = $a; 

            //---------------
            //Manager::$test_mode && dump(['connectKeyColumn exists - '.$this->relation->connectKeyColumn(), 'adding where: ' => $a, 'summary $where: ' => $where]);
            //---------------
        }

        $sql = 
           "SELECT 
                * 
            FROM 
                " . $this->relation->connectTable() . " 
            WHERE 
                " . implode(' AND ', $where);

        //---------------
        Manager::$test_mode && dump('sql: '. preg_replace('/\s\s+/', ' ', $sql));
        //---------------        
        
        $connected_records = DB::select($sql);

        //---------------
        Manager::$test_mode && dump('$connected_records: ', $connected_records);
        //---------------     

        $connected_index = [];
        if($connected_records){

            $related_ids = collect($connected_records)->pluck($this->relation->srcIdColumn())->toArray();

            $this->getRelatedRecordsByIds($related_ids); 

            //---------------
            Manager::$test_mode && dump([
                'stage' => 'GOT related records',
                '$connected_records' => $connected_records,
                '$related_ids' => $related_ids,
                '$related_records' => $this->related_records,
                '$this->relation->srcIdColumn()' => $this->relation->srcIdColumn(),
                '$this->relation->connectIdColumn()' => $this->relation->connectIdColumn(),
                '$this->relation->srcTable()' => $this->relation->srcTable(),
                '$this->relation->srcPackage()' => $this->relation->srcPackage(),
            ]);
            //--------------- 

            
            if(!empty($this->related_records) && !empty($connected_records)){
                foreach($connected_records as $connected_record){
                    $connected_index[$connected_record->{$this->relation->connectIdColumn()}][] = $this->related_records[$connected_record->{$this->relation->srcIdColumn()}];
                }
                
            }
            
        } 
        
        return $connected_index;

    }

    protected function _saveRelatedRecords()
    {
        if($this->relation->isImage()){
            $this->saveRelatedImages();
        } else {
            $this->saveRelateds();
        }
    }

    public function getConnectedIdsBySrc($src_identificators, $operator = 'OR')
    {

        $whereIds = [];
        foreach($src_identificators as $src_identificator){
            $whereIds[] = $this->relation->srcIdColumn() . "  = " . $src_identificator;        
        }
        $whereIds = implode(' '.$operator.' ', $whereIds);
        
        $sql = 'SELECT * FROM ' . $this->relation->connectTable() . ' WHERE ('.$whereIds.')';

        if($this->relation->srcTypeColumn()){
            $sql .= ' AND ' . $this->relation->srcTypeColumn() . ' = "' . $this->relation->srcPackage() . '"';
        }

        if($this->relation->connectKeyColumn()){
            $sql .= ' AND ' . $this->relation->connectKeyColumn() . ' = "' . $this->key . '"';
        }

        return collect(DB::select($sql))->pluck($this->relation->connectIdColumn())->toArray();

    }

    protected function deleteExistingConnectRecords()
    {
        $builder =  DB::table($this->relation->connectTable())
            ->where($this->relation->connectIdColumn(), $this->id);

        if($this->relation->connectTypeColumn()){
            $builder->where($this->relation->connectTypeColumn(), $this->main_table);
        }

        if($this->relation->connectKeyColumn()){
            $builder->where($this->relation->connectKeyColumn(), $this->key);
        }

        if($this->relation->srcTypeColumn() && $this->relation->srcPackage()){
            $builder->where($this->relation->srcTypeColumn(), $this->relation->srcPackage());
        }

        $builder->delete();
    }

    protected function saveRelateds()
    {
        $this->deleteExistingConnectRecords();

        if(!empty($this->data)){
            foreach($this->data as $rec){
                if($rec['id'] == 0){
                    $rec['id'] = $this->addNewRelatedRec($rec);
                } 

                $set = [
                    $this->relation->connectIdColumn() => $this->id,
                ];

                if($this->relation->connectTypeColumn()){
                    $set[$this->relation->connectTypeColumn()] = $this->main_table;
                }

                if($this->relation->connectKeyColumn()){
                    $set[$this->relation->connectKeyColumn()] = $this->key;
                }

                if($this->relation->srcIdColumn()){
                    $set[$this->relation->srcIdColumn()] = $rec['id'];
                }

                if($this->relation->srcTypeColumn() && $this->relation->srcPackage()){
                    $set[$this->relation->srcTypeColumn()] = $this->relation->srcPackage();
                }

                DB::table($this->relation->connectTable())->insert($set);
            }
        }

  
    }

    protected function saveRelatedImages()
    {
        
        $this->deleteExistingConnectRecords();

        $ids = $this->saveRelatedImagesWithImageTable();

        if(!empty($ids)){
            
            $position = 0;
            foreach($ids as $image_id){
                $set = [
                    $this->relation->connectIdColumn() => $this->id,
                    $this->relation->connectTypeColumn() => $this->main_table,
                    $this->relation->connectKeyColumn() => $this->key,
                    $this->relation->srcIdColumn() => $image_id,
                    $this->relation->srcTypeColumn() => $this->relation->srcTable(),
                    'position' => $position++
                ];
                DB::table($this->relation->connectTable())->insert($set);
            }
        }
    }

    protected function addNewRelatedRec($set) 
    {
        if(isset($set['id'])){
            unset($set['id']);
        }

        $set['active'] = 1;

        $pm = new Manager($this->relation->srcPackage());

        return DB::table($pm->package->scheme->getTable())->insertGetId($set);
    }

}

<?php

namespace Doxa\Core\Libraries\ModuleManager\Relation\modes;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Doxa\Core\Libraries\ModuleManager\Manager;
use Doxa\Core\Libraries\ModuleManager\Relation\RelationBase;

class WithSrcTable extends RelationBase
{
    protected function _attachRelatedRecords()
    {
        $ids = collect($this->records)->pluck('id')->toArray();
        
        $connected_index = $this->getConnectedRecordsIndex($ids);

        foreach($this->records as $record){
            if(!empty($connected_index[$record->id])){
                $record->{$this->relation->connectKey()} = $connected_index[$record->id];
            } else {
                $record->{$this->relation->connectKey()} = [];
            }
        }

    }

    protected function _saveRelatedRecords()
    {
        if($this->relation->isImage()){
            $this->saveRelatedImages();
        } else {
            //$this->saveRelateds();
        }
    }

    protected function saveRelatedImages()
    {
        $where = [
            $this->relation->connectIdColumn() => $this->id,
            $this->relation->connectTypeColumn() => $this->main_table, 
            $this->relation->connectKeyColumn() => $this->key,
            $this->relation->srcTypeColumn() => 'images',
        ];

        if (empty($this->data)) {
            DB::table($this->relation->srcTable())->where($where)->delete();
            return;
        }

        $position = 0;

        $previousRecords = DB::table($this->relation->srcTable())->where($where)->get()->keyBy('id');

        foreach ($this->data as $rec) {
            if(isset($rec['file'])){
                if ($rec['file'] instanceof UploadedFile) {
                    $file = $rec['file'];
                    if(str_contains($file->getMimeType(), 'image')){
                        if(str_contains($file->getMimeType(), 'svg')){
                            $path = $this->getImagesDirectory($this->relation->srcTable()) . '/' . Str::random(10) . $this->id . '.svg';
                            Storage::put($path, file_get_contents($file));
                        } else {
                            $hash =$this->writeImageOrGetExistingHash($file);

                            $set = [
                                $this->relation->connectIdColumn() => $this->id,
                                $this->relation->connectTypeColumn() => $this->main_table,
                                $this->relation->connectKeyColumn() => $this->key,
                                $this->relation->srcTypeColumn() => 'images',
                                'ext' => 'webp',
                                'hash' => $hash,
                                'position' => ++$position
                            ];

                            DB::table($this->relation->srcTable())->insert($set);

                        }

                    }
                }
            } else {
                unset($previousRecords[$rec['id']]);
                DB::table($this->relation->srcTable())->where('id', $rec['id'])->update(['position' => ++$position]);
            }

        }     
        
        if(!empty($previousRecords)){
            foreach ($previousRecords as $id => $rec) {
                DB::table($this->relation->srcTable())->where('id', $id)->delete();
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
                " . $this->relation->srcTable() . " 
            WHERE 
                " . implode(' AND ', $where);

        if($this->relation->isImage()){
            $sql .= " ORDER BY position ASC";
        }        

        //---------------
        Manager::$test_mode && dump('sql: '. preg_replace('/\s\s+/', ' ', $sql));
        //---------------        
        
        
        $related_records = DB::select($sql);

        //---------------
        Manager::$test_mode && dump('$related_records: ', $related_records);
        //---------------        

        $connected_index = [];
        if($related_records){
            $connected_index = [];
            foreach($related_records as $related_rec){
                $this->relation->isImage() && $related_rec = $this->addUrl($related_rec, $this->relation->srcTable());
                $connected_index[$related_rec->{$this->relation->connectIdColumn()}][] = $related_rec;
            }
        }
        
        //---------------
        Manager::$test_mode && dump('$related_records: ', $related_records);
        //---------------     
        return $connected_index;

    }
}

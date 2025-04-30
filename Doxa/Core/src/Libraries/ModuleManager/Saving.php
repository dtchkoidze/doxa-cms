<?php

namespace Doxa\Core\Libraries\ModuleManager;

use Illuminate\Support\Str;
use Doxa\Core\Libraries\Chlo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Helpers\Logging\Clog;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Doxa\Core\Libraries\ModuleManager\Relation\RelationAdapter;

trait Saving
{
    protected $id;

    protected $data;

    protected $set = [];

    protected $field;

    protected $field_types_ignore_for_base_save = [
        'image',
        'related',
        //'copied_from',
    ];

    /************************************************************************/
    /*************************** Save & Updates *****************************/
    /************************************************************************/

    public function save($data, $id, $action = 'update')
    {
        $this->data = $data;

        $this->id = $id;

        $this->action = $action;

        $this->makeValidation();

        if (empty($this->validator->errors()->messages())) {
            if(!$this->processSave()){
                return $this->validator;
            }
            return (int) $this->id;
        } else {
            return $this->validator;
        }
    }

    public function update($set = null, $id = null, $table = null)
    {
        if($this->query_builder && $set){
            $this->query_builder->update($set);
            $this->query_builder = null;
            return true;
        }

        if(!$id && !$this->operation_id){
            return false;
        }

        if(!$set && !$this->set){
            return false;
        }

        $id = $id ?: $this->operation_id;
        $set = $set ?: $this->set;

        //dump('$table: ',$table);

        if($table){
            if($table == 'variation'){
                $this->operation_table = $this->variations_table;
            } else {
                $this->operation_table = $this->table;
            }
        } else {
            if(!$this->operation_table){
                $this->operation_table = $this->table;
            }
        }

        DB::table($this->operation_table)->where('id', $id)->update($set);

        return true;

    }

    public function delete($id = null)
    {
        if($this->query_builder){
            //dd('qb');
            $this->query_builder->delete();
            $this->query_builder = null;
            return true;
        }
        //dump('---1');
        if($id && $this->operation_table){

            //DB::enableQueryLog();
            //ump($id, $this->operation_table);
            // dd([
            //     'id' => $id,
            //     'table' => $this->operation_table
            // ]);
            if($this->package->hasVariations()){
                DB::table($this->variations_table)->where('src_id', $id)->delete();
            }
            DB::table($this->operation_table)->where('id', $id)->delete();
            //dump(DB::getQueryLog());
            return true;
        }
        //dd('false');
        return false;
    }

    public function clearVariation($id)
    {
        foreach ($this->package->getEditingVariationFields() as $key => $field) {
            if ($key == 'active') {
                $set[$key] = 0;
            } else {
                $set[$key] = '';
            }
        }
        DB::table($this->variations_table)->where('id', $id)->update($set);
        return true;
    }

    public function clearVariations($base_ids, $channel_id, $locale_id)
    {
        // $base_ids = [1000, 2000, 3000];
        // $channel_id = 100;
        // $locale_id = 200;

        $set = [];
        foreach($this->package->getEditingVariationFields() as $key => $field) {
            if($key == 'active'){
                $set[$key] = 0;
            } else {
                $set[$key] = '';
            }
        }

        //DB::enableQueryLog();

        $builder = DB::table($this->variations_table);
        if($channel_id){
            $builder->where('channel_id', $channel_id);
        }
        $builder->where('locale_id', $locale_id);

        foreach($base_ids as $base_id){
            $_builder = clone $builder;
            //dump($set);
            $_builder->where('src_id', $base_id)->update($set);
            //dump(DB::getQueryLog());
        }

        return true;
    }

    public function deleteVariationsByBaseIds($base_ids)
    {
        DB::table($this->variations_table)->whereIn('src_id', $base_ids)->delete();
        return true;
    }

    protected function processSave()
    {
        if($this->id == 0 && $this->package->hasVariations() &&  $this->package->isEditingFieldExists('active','variation')){
            if(!$this->checkOneMinimumActiveVariationRecord()){
                $this->validator->errors()->add(
                    'alert',
                    omniTrans($this->module, 'no-active-variation-record')
                );
                return false;
            }
        }

        if($this->id != 0 && $this->package->isHistoryEnabled()){
            $this->writeHistory('rev');
        }

        $this->saveBaseData();

        if($this->package->hasVariations()){
            $this->saveVariations();
        }

        // if($this->pm->package->cache_required && method_exists($this, 'toCache')){
        //     $this->toCache();
        // }

        return true;
    }

    protected function saveBaseData()
    {
        //dd($this->data);

        $set = [];
        foreach($this->package->getEditingFields() as $key => $field) {
            if(isset($this->data[$key])){
                if(!in_array($field->type, $this->field_types_ignore_for_base_save)){
                    $set[$key] = $this->data[$key];
                }
            }
        }

        if(empty($set) && $this->package->hasVariations()){
            $set['active'] = 1;
        }

        if($this->id == 0 || $this->action == 'store'){
            $this->id = DB::table($this->table)->insertGetId($set);
        } else {
            DB::table($this->table)->where('id', $this->id)->update($set);
        }

        foreach($this->package->getEditingFields() as $key => $field) {
            if($field->type == 'related' || $field->type == 'image'){
                $relation = $this->package->scheme->getRelationByKey($key);
                if($relation){
                    $relationHandler = RelationAdapter::getHandler($relation, $this->package);
                    $relationHandler->saveRelatedRecords($this->id, $this->data);
                }
            }
        }
    }

    protected function saveVariations()
    {
        //dump(Chlo::altAsAssocById(), $this->data['variation']);
        foreach(Chlo::altAsAssocById() as $channel_id => $channel) {
            foreach($channel->locales as $locale_id => $locale) {
                //dump('$channel_id: '.$channel_id.', $locale_id: '.$locale_id);
                if(isset($this->data['variation'][$channel_id][$locale_id])){
                    //dump('$this->data[variation]: ',$this->data['variation']);
                    //dump('$this->data[variation][$channel_id][$locale_id]: ',$this->data['variation'][$channel_id][$locale_id]);
                    $existing_variation = $this->getVariation($channel_id, $locale_id);

                    $set = [];
                    foreach($this->data['variation'][$channel_id][$locale_id] as $key => $value) {
                        $set[$key] = $value ?: '';
                    }

                    $set['channel_id'] = $channel_id;
                    $set['locale_id'] = $locale_id;
                    $set['src_id'] = $this->id;

                    if(
                        !$existing_variation
                        &&
                        $this->package->scheme->isVariationHasPosition()
                    ){
                        $set['position'] = $this->getVariationNextPosition($channel_id, $locale_id);
                    }

                    // if($this->package->scheme->isVariationHasAdminId()){
                    //     $set['admin_id'] = auth()->guard('admin')->user()->id;
                    // }

                    if($this->scheme->isVariationHasCreatedAt()){
                        if(!$existing_variation){
                            $set['created_at'] = date('Y-m-d H:i:s');
                        }
                    }

                    if($existing_variation){
                        DB::table($this->variations_table)->where('id', $existing_variation->id)->update($set);
                    } else {
                        DB::table($this->variations_table)->insertGetId($set);
                    }

                    //dump('$this->scheme->isVariationHasUpdatedAt(): ',$this->scheme->isVariationHasUpdatedAt());

                    if ($this->scheme->isVariationHasUpdatedAt() && $existing_variation){

                        $after_save = $this->getVariation($channel_id, $locale_id);
                        $before_save = $existing_variation;

                        //dump((array)$after_save !== (array)$before_save);

                        if((array)$after_save !== (array)$before_save) {
                            
                            $_set = [
                                'updated_at' => date('Y-m-d H:i:s'),
                                //'admin_id' => auth()->guard('admin')->user()->id
                            ];
                            DB::table($this->variations_table)->where('id', $existing_variation->id)->update($_set);
                        }

                    }

                } else {
                    dump('no');
                }
            }
        }

        //dd(1);
    }

    // protected function saveRelatedWithConnectTable($relation)
    // {
    //     if($relation->isImage()){
    //         $this->saveRelatedImagesWithConnectTable($relation);
    //         return;
    //     }

    //     $key = $this->field->key;

    //     [$table, $type] = $this->getFieldDependsData();

    //     $builder =  DB::table($relation->connectTable());

    //     $builder->where([
    //         $relation->connectIdColumn() => $this->id,
    //         $relation->connectTypeColumn() => $table,
    //         $relation->connectKeyColumn() => $relation->connectKey()
    //     ]);

    //     if($relation->srcTypeColumn() && $relation->srcPackage()){
    //         $builder->where($relation->srcTypeColumn(), $relation->srcPackage());
    //     }

    //     $builder->delete();

    //     if(!empty($this->data[$key])){
    //         foreach($this->data[$key] as $rec){
    //             if($rec['id'] == 0){
    //                 $rec['id'] = $this->addNewRelatedRec($rec, $relation);
    //             }

    //             $set = [
    //                 $relation->connectIdColumn() => $this->id,
    //                 $relation->connectTypeColumn() => $table,
    //                 $relation->connectKeyColumn() => $relation->connectKey(),
    //                 $relation->srcIdColumn() => $rec['id'],
    //             ];

    //             if($relation->srcTypeColumn() && $relation->srcPackage()){
    //                 $set[$relation->srcTypeColumn()] = $relation->srcPackage();
    //             }

    //             DB::table($relation->connectTable())->insert($set);
    //         }
    //     }
    // }

    // protected function saveRelatedImagesWithConnectTable($relation)
    // {
    //     //dump(__METHOD__);

    //     $key = $this->field->key;

    //     [$table, $type] = $this->getFieldDependsData();

    //     //$relation->info();

    //     $where = [
    //         $relation->connectIdColumn() => $this->id,
    //         $relation->connectTypeColumn() => $table,
    //         $relation->connectKeyColumn() => $key,
    //         $relation->srcTypeColumn() => $relation->srcTable(),
    //     ];

    //     if (empty($this->data[$key])) {
    //         DB::table($relation->connectTable())->where($where)->delete();
    //         return;
    //     }

    //     $position = 0;

    //     if(!$this->copied_from){
    //         $previousRecords = DB::table($relation->connectTable())->where($where)->get()->keyBy('id');
    //     }

    //     foreach ($this->data[$key] as $rec) {
    //         if(isset($rec['file'])){
    //             if ($rec['file'] instanceof UploadedFile) {
    //                 $file = $rec['file'];
    //                 if(str_contains($file->getMimeType(), 'image')){
    //                     if(str_contains($file->getMimeType(), 'svg')){
    //                         $path = $this->getDirectory($table) . '/' . Str::random(10) . $this->id . '.svg';
    //                         Storage::put($path, file_get_contents($file));
    //                     } else {
    //                         // make image
    //                         $manager = new ImageManager();
    //                         $image = $manager->make($file)->encode('webp');

    //                         // create temp image to compare with existings
    //                         $name = Str::random(10) . $this->id;
    //                         $temp_path = $this->getImagesTempDirectory() . '/' . $name . '.webp';
    //                         Storage::put($temp_path, $image);

    //                         $temp_added_path = Storage::url($temp_path);


    //                         $hash = hash_file('md5', $temp_added_path);
    //                         //dump('$hash: ',$hash);

    //                         $exists = $this->checkImageExistsByHash($hash, $relation->srcTable());
    //                         if(!$exists){
    //                             $folder = substr($hash, 0, 2);
    //                             $new_path = $this->getImagesDirectory($relation->srcTable()) . '/' . $folder . '/' . $hash . '.webp';
    //                             //dump('$new_path: ',$new_path);
    //                             Storage::copy($temp_path, $this->getImagesDirectory($relation->srcTable()) . '/' . $folder . '/' . $hash . '.webp');
    //                             Storage::delete($temp_path);

    //                             $set = [
    //                                 'hash' => $hash,
    //                                 'ext' => 'webp',
    //                             ];

    //                             $image_id = DB::table($relation->srcTable())->insertGetId($set);

    //                         } else {
    //                             $image_id = $exists->id;
    //                         }

    //                         $set = [
    //                             $relation->connectIdColumn() => $this->id,
    //                             $relation->connectTypeColumn() => $table,
    //                             $relation->connectKeyColumn() => $key,
    //                             $relation->srcIdColumn() => $image_id,
    //                             $relation->srcTypeColumn() => $relation->srcTable(),
    //                             'position' => ++$position,
    //                         ];

    //                         DB::table($relation->connectTable())->insertGetId($set);

    //                     }

    //                 }
    //             }
    //         } else {
    //             if(!$this->copied_from){
    //                 unset($previousRecords[$rec['ctid']]);
    //                 DB::table($relation->connectTable())->where('id', $rec['ctid'])->update(['position' => ++$position]);
    //             }
    //         }

    //     }

    //     if(!$this->copied_from && !empty($previousRecords)){
    //         foreach ($previousRecords as $id => $rec) {
    //             DB::table($relation->connectTable())->where('id', $id)->delete();
    //         }
    //     }

    // }

    // protected function saveRelatedWithoutConnectTable($relation)
    // {

    // }

    protected function saveRelatedWithSrcTable($relation)
    {
        if($relation->isImage()){
            $this->saveRelatedImagesWithSrcTable($relation);
            return;
        }
    }

    protected function saveRelatedImagesWithSrcTable($relation)
    {
        $key = $this->field->key;

        [$table, $type] = $this->getFieldDependsData();

        //$relation->info();

        $where = [
            $relation->connectIdColumn() => $this->id,
            $relation->connectTypeColumn() => $table,
            $relation->connectKeyColumn() => $key,
            $relation->srcTypeColumn() => 'images',
        ];

        if (empty($this->data[$key])) {
            DB::table($relation->srcTable())->where($where)->delete();
            return;
        }

        $position = 0;

        if(!$this->copied_from){
            $previousRecords = DB::table($relation->srcTable())->where($where)->get()->keyBy('id');
        }

        foreach ($this->data[$key] as $rec) {
            if(isset($rec['file'])){
                if ($rec['file'] instanceof UploadedFile) {
                    $file = $rec['file'];
                    if(str_contains($file->getMimeType(), 'image')){
                        if(str_contains($file->getMimeType(), 'svg')){
                            $path = $this->getDirectory($table) . '/' . Str::random(10) . $this->id . '.svg';
                            Storage::put($path, file_get_contents($file));
                        } else {
                            // make image
                            $manager = new ImageManager();
                            $image = $manager->make($file)->encode('webp');

                            // create temp image to compare with existings
                            $name = Str::random(10) . $this->id;
                            $temp_path = $this->getImagesTempDirectory() . '/' . $name . '.webp';
                            Storage::put($temp_path, $image);

                            $temp_added_path = Storage::url($temp_path);


                            $hash = hash_file('md5', $temp_added_path);
                            //dump('$hash: ',$hash);

                            $set = [
                                $relation->connectIdColumn() => $this->id,
                                $relation->connectTypeColumn() => $table,
                                $relation->connectKeyColumn() => $key,
                                $relation->srcTypeColumn() => 'images',
                                'ext' => 'webp',
                                'position' => ++$position
                            ];

                            $exists = $this->checkImageExistsByHash($hash, $relation->srcTable());
                            if(!$exists){
                                $folder = substr($hash, 0, 2);
                                $new_path = $this->getImagesDirectory($relation->srcTable()) . '/' . $folder . '/' . $hash . '.webp';
                                //dump('$new_path: ',$new_path);
                                Storage::copy($temp_path, $new_path);
                                Storage::delete($temp_path);

                                $set['hash'] = $hash;

                            } else {
                                $set['hash'] = $exists->hash;
                            }

                            DB::table($relation->srcTable())->insert($set);

                        }

                    }
                }
            } else {
                if(!$this->copied_from){
                    unset($previousRecords[$rec['id']]);
                    DB::table($relation->srcTable())->where('id', $rec['id'])->update(['position' => ++$position]);
                }
            }

        }

        if(!$this->copied_from && !empty($previousRecords)){
            foreach ($previousRecords as $id => $rec) {
                DB::table($relation->srcTable())->where('id', $id)->delete();
            }
        }

    }

    protected function checkOneMinimumActiveVariationRecord()
    {
        foreach(Chlo::altAsAssocById() as $channel_id => $channel) {
            foreach($channel->locales as $locale_id => $locale) {
                if(isset($this->data['variation'][$channel_id][$locale_id]) && !empty($this->data['variation'][$channel_id][$locale_id]['active'])){
                    return true;
                }
            }
        }
    }

    protected function getFieldDependsData()
    {
        if($this->field && !$this->field->is_variation){
            $table = $this->table;
            $type = 'base';
        } else {
            $table = $this->variations_table;
            $type = 'variation';
        }

        return [$table, $type];
    }

    public function getImagesDirectory($table): string
    {
        return 'images/' . $table;
    }

    public function getImagesTempDirectory(): string
    {
        return 'images/temp';
    }

    public function checkImageExistsByHash($hash, $table)
    {
        return DB::table($table)->where('hash', $hash)->first();
    }

    public function updatePositions($data)
    {
        $position = 1;
        if(strpos($data['column'], '_variation') !== false){
            foreach($data['ids'] as $id){
                DB::table($this->variations_table)->where('id', $id)->update(['position' => $position]);
                $position++;
            }
        } else {
            foreach($data['ids'] as $id){
                DB::table($this->table)->where('id', $id)->update(['position' => $position]);
                $position++;
            }
        }
    }

    /**** END ***************************************************************/
}


<?php

namespace Doxa\Core\Libraries\ModuleManager\Relation;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Doxa\Core\Libraries\ModuleManager\Manager;
use Doxa\Core\Libraries\ModuleManager\AdminPackageManager;

class RelationBase
{

    protected $records = [];

    protected $related_records = [];

    protected $id;
    protected $data;
    protected $full_data;

    protected $main_table;

    protected $ids = [];

    protected $key;

    protected $mode;

    protected $errors = [];


    public function __construct(protected $relation, protected $package = null, protected $request_config)
    {
        $this->key = $this->relation->connectKey();
        $this->mode = $this->relation->mode();

        if($this->package){
            $this->main_table = $this->relation->isBase() ? $this->package->scheme->getTable() : $this->package->scheme->getVariationTable();
        } 
        
        Manager::$test_mode && dump('---- START ---------------------- RelationHandling - key: '.$this->key.', type: '.$this->relation->type().', mode: '.$this->mode .' ------------------------------');
        
    }

    /**
     * Attaches related records to the given records.
     *
     * @param array $records An array of records to attach related records to.
     *
     * @return void
     */
    public function attachRelatedRecords(array $records){

        if(empty($records)){
            Manager::$test_mode && dump('No records to attach related');
            return;
        }

        $this->records = $records;

        $this->ids = collect($records)->pluck('id')->toArray();

        Manager::$test_mode && dump([
            'stage' => 'STARTING related to records attachment',
            '$this->records' => $this->records,
            '$this->ids' => $this->ids, 
            'sizeof' => count($this->ids)
        ]);

        $this->_attachRelatedRecords();

        //---------------
        Manager::$test_mode && dump('$this->records: ', $this->records);
        //--------------- 

        Manager::$test_mode && dump('---- END ------------------------ RelationHandling - key: '.$this->relation->connectKey().', type: '.$this->relation->type().')------------------------------');
    }

    /**
     * Saves related records. It takes the id of the main record and the
     * full data of the record and saves the related records.
     *
     * @param int $id the id of the main record
     * @param array $full_data the full data of the record
     *
     * @return void
     */
    public function saveRelatedRecords($id, $full_data)
    {
        $this->id = $id;
        $this->full_data = $full_data;
        if(!empty($full_data[$this->key])){
            $this->data = $full_data[$this->key];
        } else {
            $this->data = [];
        }

        //dump('$this->data: ', $this->data);

        $this->_saveRelatedRecords();
    }

    protected function getRelatedRecordsByIds($ids): void
    {
        $this->related_records = [];

        $identificator_column = $this->relation->srcKey() ?? 'id';

        if($this->relation->srcPackage()){

            foreach($ids as &$identificator){
                if(!is_numeric($identificator)){
                    $identificator = "'".$identificator."'";
                }
            }

            $where = [$identificator_column.' IN' => $ids];

             //---------------
            Manager::$test_mode && dump([
                'stage' => 'Calling '.$this->relation->srcPackage() . 'getRelationList() method to get related records',
                '$where' => $where,
            ]);
            //---------------

            //dd('this fucken place');

            // ---------- OLD -----------
            // $pm = new AdminPackageManager(
            //     $this->relation->srcPackage(), 
            //     where: $where,
            //     locale: 'current',
            // );

            // if($pm->package->defaultChannelOnly()){
            //     $pm->channel(1);
            // } else {
            //     $pm->channel('current');
            // }
            // ---END---- OLD -----------

            // ---------- NEW -----------
            $pm = new Manager(
                $this->relation->srcPackage(), 
                where: $where,
            );
            $pm->with(['related', 'images']);

            if(!$pm->package->defaultChannelOnly()){
                $pm->channel('current');
            }

            if($pm->package->hasVariations()){
                $pm->locale('current');
            }
            // ---END---- NEW -----------

            $list_fields = [];
            if($identificator_column !== 'id'){
                $list_fields = [$identificator_column];
            }
            
            $this->related_records = $pm->getRelationList($list_fields, $full = true);

            //dd('$this->related_records: ', $this->related_records);
        } else {
            if($this->relation->srcTable()){
                $builder = DB::table($this->relation->srcTable())->whereIn($identificator_column, $ids)->where('active', 1);
                if($this->relation->listFields()){
                    $builder->select($this->relation->listFields());
                }
                $this->related_records = $builder->get();
            }
        }

        $this->related_records = collect($this->related_records)->keyBy($identificator_column);

        if(!empty($this->related_records) && $this->relation->isImage()){
            $this->addUrlToRelatedRecords();
        }
    }

    protected function addUrlToRelatedRecords(){
        foreach($this->related_records as &$rec){
            $this->addUrl($rec, $this->relation->srcTable());
        }
    }

    protected function addUrl($rec, $src_table)
    {
        if($rec->ext && $rec->hash){
            if(!$rec->folder){
                $rec->folder = substr($rec->hash, 0, 1);
            }
            $rec->url = '/storage/images/' . $rec->folder . '/' . $rec->hash . '.' . $rec->ext;
            if($rec->ext == 'webp'){
                $rec->thumb_url = '/storage/images/thumbs/' . $rec->folder . '/' . $rec->hash . '.' . $rec->ext;
            }
        } else {
            $rec->url = '/storage/' . $rec->path;
        }

        return $rec;
    }

    public function getImagesDirectory($table): string
    {
        return 'images';
        //return 'images/' . $table;
    }

    public function getImageThumbDirectory($table): string
    {
        return 'images/thumbs';
        //return 'images/' . $table;
    }

    public function getImagesTempDirectory(): string
    {
        return 'images/temp';
    }

    public function checkImageExistsByHash($hash, $table)
    {
        return DB::table($table)->where('hash', $hash)->first();
    }

    protected function saveRelatedImagesWithImageTable()
    {
        $ids = [];

        foreach ($this->data as $rec) {
            if(isset($rec['file'])){
                if ($rec['file'] instanceof UploadedFile) {
                    $file = $rec['file'];
                    if(str_contains($file->getMimeType(), 'image')){
                        if(str_contains($file->getMimeType(), 'svg')){
                            $ext = 'svg';
                        } else {
                            $ext = 'webp';
                        }

                        $ids[] = $this->writeImageOrGetExistingHash($file, $ext);
                    }
                }
            } else {
                $ids[] = $rec['id'];
            }

        } 

        return $ids;

    }

    protected function writeImageOrGetExistingHash($file, $ext)
    {
        if($ext == 'webp'){
            //$driver = new Driver();
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file)->toWebp();
        }

        if($ext == 'svg'){
            $image = file_get_contents($file);
        }

        $name = Str::random(10) . $this->id;
        $temp_path = $this->getImagesTempDirectory() . '/' . $name . '.'.$ext;
        Storage::put($temp_path, $image);

        $temp_added_path = Storage::url($temp_path);
        
        $hash = hash_file('md5', $temp_added_path);

        $folder = substr($hash, 0, 1);
        $new_path = $this->getImagesDirectory($this->relation->srcTable()) . '/' . $folder . '/' . $hash . '.'.$ext;

        dd($new_path);

        // create files
        if(!Storage::exists($new_path)){
            //dump('!Storage::exists($new_path): ', $new_path);
            Storage::copy($temp_path, $new_path);
        }
        if($ext == 'webp'){
            //$thumb_path = $this->getImageThumbDirectory($this->relation->srcTable()) . '/' . $folder . '/' . $hash . '.'.$ext;
            $this->makeThumb($temp_path, $hash);
        }
        Storage::delete($temp_path);
        // -- end create files

        $exists = $this->checkImageExistsByHash($hash, $this->relation->srcTable());
        if(!$exists){
            // $folder = substr($hash, 0, 1);
            // $new_path = $this->getImagesDirectory($this->relation->srcTable()) . '/' . $folder . '/' . $hash . '.'.$ext;
            // Storage::copy($temp_path, $new_path);

            // if($ext == 'webp'){
            //     //$thumb_path = $this->getImageThumbDirectory($this->relation->srcTable()) . '/' . $folder . '/' . $hash . '.'.$ext;
            //     $this->makeThumb($temp_path, $hash);
            // }    

            //Storage::delete($temp_path);

            $set = [
                'ext' => $ext,
                'active' => 1,
                'hash' => $hash,
            ];

            return DB::table($this->relation->srcTable())->insertGetId($set);
        } else {
            // if(!Storage::exists($new_path)){
            //     dump('!Storage::exists($new_path): ', $new_path);
            //     Storage::copy($temp_path, $new_path);
            // }
            //Storage::delete($temp_path);
            return $exists->id;
        }


    }

    public function getErrors()
    {
        Clog::write('Relation', 'getErrors: '.json_encode($this->errors));
        return $this->errors;
    }

    public function makeThumb($src, $hash, $desired_width = 100, $desired_height = 100) {

        //dump('$src: '. $src);
        //dump('Storage::url($src): '. Storage::url($src));

        $src = Storage::url($src);
        /* read the source image */
        $source_image = imagecreatefromwebp($src);

        $width = imagesx($source_image);
        $height = imagesy($source_image);

        $dest_height = $height;
        $dest_width = $width;

        if($width > $height) {
            if($width > $desired_width) {
                $dest_height = floor($height * ($desired_width / $width));
                $dest_width = $desired_width;
            } 
        } else {
            if($height > $desired_height) {
                $dest_width = floor($width * ($desired_height / $height));
                $dest_height = $desired_height;
            }
        }

        $virtual_image = imagecreatetruecolor($dest_width, $dest_height);

        imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $dest_width, $dest_height, $width, $height);

        $dir = 'storage/' . $this->getImageThumbDirectory('images') . '/' . substr($hash, 0, 1);
        if(!is_dir($dir)){
            mkdir($dir, 0744, true);
        }

        imagewebp($virtual_image, $dir . '/' . $hash . '.webp');
    }

    // protected function isOmniRequest()
    // {
    //     return !empty($this->request_config['omni']);
        
    //     // if(empty($this->request_config)){
    //     //     return false;
    //     // }

    //     // if(isset($this->request_config)){

    //     // }
    // }
}

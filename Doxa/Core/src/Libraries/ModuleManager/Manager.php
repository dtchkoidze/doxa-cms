<?php

namespace Doxa\Core\Libraries\ModuleManager;

use Doxa\Core\Libraries\Chlo;
use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Libraries\Package\Scheme;
use Illuminate\Support\Facades\Storage;
use Doxa\Core\Libraries\Package\Package;
use Doxa\Core\Libraries\QueryBuilder\Builder;
use Doxa\Core\Libraries\ModuleManager\Relation\RelationAdapter;

class Manager
{

    use Chain, Getters, Saving, ModuleValidator, History;

    protected static self|null $instance = null;

    public string $module = '';

    protected object $config;

    protected string $method = '';

    protected ?int $channel_id = null;
    protected ?int $locale_id = null;
    protected bool $variations_required = true;

    public ?Package $package = null;
    public Scheme $scheme;

    protected string $table;
    protected ?string $variations_table = null;

    protected $operands;
    protected $base_prefixes;
    protected $variation_prefixes;

    // protected $nested_variations = false;
    // protected $assoc_variations = false;

    protected $log_name = 'builder';
    protected $log_level = 1;

    protected $request_config = [];


    protected $fetch;
    protected $result;

    protected $wheres;

    // local config

    protected Builder $builder;

    protected $item;

    protected $select = [];

    protected $select_build = [];

    protected $where_build = [];

    protected $from_build = [];

    protected $ids = [];

    protected $request_type = null;

    public $base_records = [];
    public $variation_records = [];

    protected $variations_index = [];
    protected $variations_assoc_index = [];

    protected $relations = [];

    protected $dump = [];

    public $copied_from = false;

    protected $QB;

    protected $qbm = false;

    protected $order = [];

    protected $limit;

    protected $page = 1;

    protected String $current_channel_id;
    protected String $current_locale_id;

    public Object|null $current_record = null;

    public static $test_mode = false;

    public function __construct(
        $module,
        $package = null,
        $config = null,
        $with = null,
        $where = null,
        $select = null,
        $channel = null,
        $locale = null,
        $method = null,
    ){
        $this->module = $module;

        $package && $this->package($package);

        $this->initialize();

        $config && $this->config($config);
        $with && $this->with($with);
        $where && $this->where($where);
        $channel && $this->channel($channel);
        $locale && $this->locale($locale);
        $select && $this->select($select);
        $method && $this->method($method);
    }

    public static function __callStatic($method, $args)
    {

        if (static::$instance === null) {
            return false;
        }

        if(method_exists(static::$instance, $method)){
            return static::$instance->$method(...$args);
        }

        return false;
    }

    public function __call($method, $args)
    {
        if(method_exists($this, $method)){
            return $this->$method(...$args);
        }

        // if(method_exists($this->builder, $method)){
        //     $this->builder->$method(...$args);
        //     return $this;
        // }

        return false;
    }

    public static function pm(
        $module,
        $package = null,
        $config = null,
        $with = null,
        $where = null,
        $select = null,
        $channel = null,
        $locale = null,
        $method = null,
    )
    {
        $pm = new Manager(
            $module,
            $package,
            $config,
            $with,
            $where,
            $select,
            $channel,
            $locale,
            $method,
        );

        return $pm;
    }


    protected function getBaseRecords()
    {
        $this->getRequestWhereBuild('base');

        // if($this->table == 'messages'){
        //     dump($this->wheres);
        //     dump($this->where_build);
        // }

        $where_str = !empty($this->where_build) ? ' WHERE ' . implode(' AND ', $this->where_build) : '';

        $select = '*';
        if(!empty($this->select)){
            $select = implode(', ', $this->getSelectArr('base'));
        }

        $order_str = '';
        $_order = [];
        if(!empty($this->order)){
            foreach($this->order as $order){
                $column = $order['column'];
                $a = explode('.', $column);
                if(count($a) > 1){
                    if($a[0] == 'base' || $a[0] == 'b' || $a[0] == $this->table){
                        $_order[] = $a[1].' '.$order['direction'];
                    }
                } else {
                    $_order[] = $column.' '.$order['direction'];
                }
            }
            if(!empty($_order)){
                $order_str = ' ORDER BY ' . implode(', ', $_order);
            }
        }

        $sql = 'SELECT ' . $select . ' FROM ' . $this->table . $where_str . $order_str;

        //---------------
        self::$test_mode && dump('sql: '. preg_replace('/\s\s+/', ' ', $sql));
        //---------------

        //dump('>>> SQL >>> base sql: ', $sql);

        //$this->base_records = '';

        $this->base_records = DB::select($sql);
    }

    protected function getSelectArr($tb)
    {
        $_select = array_keys($this->scheme->getRequiredTableFields($tb));
        foreach($this->select as $select){
            if($this->scheme->isFieldExists($select, $tb) && !in_array($select, $_select)){
                $_select[] = $select;
            }
        }
        return $_select;
    }

    protected function tryGetVariations()
    {
        if($this->variations_table && $this->isWith('variations')){
            $this->getVariationRecords();
            $this->attachVariationRecords();
        }
    }

    protected function getVariationRecords()
    {
        //dump('------------------------------'.$this->variations_table.'------------------------------');

        $this->getRequestWhereBuild('variation');
        $this->getVariationsChloWhereBuild();

        $base_ids = collect($this->base_records)->pluck('id')->toArray();

        // if($this->variations_table == 'feedback_variations'){
        //     dd('$this->where_build: ', $this->where_build);
        // }

        $this->where_build[] = 'src_id IN (' . implode(',', $base_ids) . ')';

        $select = '*';
        if(!empty($this->select)){
            $select = implode(', ', $this->getSelectArr('variation'));
        }

        $order_str = '';
        $_order = [];
        if(!empty($this->order)){
            foreach($this->order as $order){
                $column = $order['column'];
                $a = explode('.', $column);
                if(count($a) > 1){
                    if($a[0] == 'variation' || $a[0] == 'v' || $a[0] == $this->variations_table){
                        $_order[] = $a[1].' '.$order['direction'];
                    }
                }
            }
            if(!empty($_order)){
                $order_str = ' ORDER BY ' . implode(', ', $_order);
            }
        }

        $sql = 'SELECT ' . $select . ' FROM ' . $this->variations_table . ' WHERE ' . implode(' AND ', $this->where_build) . $order_str;

        //dump('>>> SQL >>> variations sql: ', $sql);

        $this->variation_records = DB::select($sql);

        if($this->variation_records){
            $this->createVariationsIndex();
        }

        //dump('$this->variation_records: ', $this->variation_records);
    }

    protected function getVariation($channel_id, $locale_id)
    {
        return DB::table($this->variations_table)->where('channel_id', $channel_id)->where('locale_id', $locale_id)->where('src_id', $this->id)->first();
    }

    protected function findVariation($id)
    {
        $this->current_record = DB::table($this->variations_table)->find($id);
        return $this->current_record;
    }

    protected function getVariationNextPosition($channel_id, $locale_id)
    {
        return DB::table($this->variations_table)->where('channel_id', $channel_id)->where('locale_id', $locale_id)->where('src_id', $this->id)->max('position') + 1;
    }

    protected function createVariationsIndex()
    {
        $this->variations_index = [];
        $this->variations_assoc_index = [];

        //dump('$this->variation_records: ',$this->variation_records);

        foreach($this->variation_records as $variation){

            if(!empty($variation->updated_at)){
                $variation->updated_at_formatted = date('d.m.Y H:i', strtotime($variation->updated_at));
            }
            // if(!empty($variation->admin_id)){
            //     $variation->admin_name = Admin::find($variation->admin_id)->name;
            // }

            $this->ids['variation'][] = $variation->id;
            $this->variations_index[$variation->src_id][] = $variation;
            if($this->getRequestConfig('assoc_variations')){
                $this->variations_assoc_index[$variation->src_id][$variation->channel_id][$variation->locale_id] = $variation;
                // if($this->package->isChannelsIgnored()){
                //     $this->variations_assoc_index[$variation->src_id][$variation->locale_id] = $variation;
                // } else {
                //     $this->variations_assoc_index[$variation->src_id][$variation->channel_id][$variation->locale_id] = $variation;
                // }
            }
        }

        //dump('>>> $this->variation_records: ', $this->variation_records);
        //dump('>>> $this->variations_index: ', $this->variations_index);
        //dump('>>> $this->variations_assoc_index: ', $this->variations_assoc_index);
    }

    protected function getVariationsChloWhereBuild()
    {
        if(!$this->package->isChannelsIgnored()){
            if($this->channel_id){
                Chlo::set(channel: $this->channel_id);
                $this->where_build[] = 'channel_id = ' . $this->channel_id;
            }
        }

        if($this->locale_id){
            Chlo::set(locale: $this->locale_id);
            $this->where_build[] = 'locale_id = ' . $this->locale_id;
        }

        // if($this->package->defaultChannelOnly()){
        //     $this->where_build[] = 'channel_id = 1';
        // } else {
        //     if(!$this->channel_id){
        //         $this->where_build[] = 'channel_id IN (' . implode(',', Chlo::getAvailableChannelIds()) . ')';
        //     } else {
        //         $this->where_build[] = 'channel_id = ' . $this->channel_id;
        //     }
        // }

        // if(!$this->locale_id){
        //     $this->where_build[] = 'locale_id IN (' . implode(',', Chlo::getAvailableLocaleIds()) . ')';
        // } else {
        //     $this->where_build[] = 'locale_id = ' . $this->locale_id;
        // }


        // if(!$this->channel_id && !$this->locale_id){
        //     $this->where_build[] = 'channel_id IN (' . implode(',', Chlo::getAvailableChannelIds()) . ')';
        //     $this->where_build[] = 'locale_id IN (' . implode(',', Chlo::getAvailableLocaleIds()) . ')';
        // } else {
        //     $this->where_build[] = 'channel_id = ' . $this->channel_id;
        //     $this->where_build[] = 'locale_id = ' . $this->locale_id;
        // }
    }

    protected function isWithContains($key)
    {
        return in_array($key, $this->with);
    }

    protected function isWith($key)
    {
        if(empty($this->with[$key])){
            return false;
        }

        return !empty($this->with[$key]) ? $this->with[$key] : false;
    }

    protected function getRequestWhereBuild($type)
    {
        //dump('getRequestWhereBuild('.$type.')', $this->wheres);

        $this->where_build = [];
        if(!empty($this->wheres)){
            foreach($this->wheres as $whereObj){
                if($whereObj->getByTable() == $type){
                    $this->where_build[] = $whereObj->getRequestString();
                }
            }
        }

    }

    protected function attachVariationRecords()
    {
        if($this->getRequestConfig('nested_variations')){
            $this->handleNestedVariations();
        } else {
            $this->mergeVariations();
        }
    }

    protected function handleNestedVariations()
    {
        foreach($this->base_records as &$record){
            if(isset($this->variations_index[$record->id])){
                $record->variations = $this->variations_index[$record->id];
            }
            if($this->getRequestConfig('assoc_variations')){
                $record->assoc_variations = $this->variations_assoc_index[$record->id];
            }
        }
    }

    protected function mergeVariations()
    {
        $stop = [
            //'active',
            'id',
            'channel_id',
            'locale_id',
            'src_id',
        ];

        $overwrite= [
            'active',
            'created_at',
            'updated_at',
        ];

        //dump('----------------------- mergeVariations() -----------------------');
        $_base_records = [];
        foreach($this->base_records as $i => &$base_record){
            if(isset($this->variations_index[$base_record->id])){
                $variation = $this->variations_index[$base_record->id][0];
                foreach($variation as $key => $value){
                    //dump($key.': '.$value);
                    if(!in_array($key, $stop)){
                        if(!isset($base_record->$key) || in_array($key, $overwrite)){
                            $base_record->$key = $value;
                        } else {

                        }
                    }
                }
                $_base_records[] = $base_record;

            } else {
                //unset($this->base_records[$i]);
            }

        }

        $this->base_records = $_base_records;
    }
























    protected function handleResult()
    {
        if($this->isNestedVariationsRequested()){
            $this->handleNestedVariations();
            if($this->isAssocVariationsRequested()){
                $this->handleAssocVariations();
            }
        }
    }

    protected function isNestedVariationsRequested()
    {
        return !empty($this->request_config['nested_variations']) && $this->request_config['nested_variations'] == true;
    }

    protected function isAssocVariationsRequested()
    {
        return !empty($this->request_config['assoc_variations']) && $this->request_config['assoc_variations'] == true;
    }

    protected function handleNestedVariations__old()
    {
        $bases = [];
        foreach($this->fetch as $rec){
            if(!isset($bases[$rec->b__id])){
                $bases[$rec->b__id] = (object)[];
            }
            $variation =  (object)[];
            foreach($rec as $key => $val){
                if(strpos($key, 'b__') === 0){
                    $bases[$rec->b__id]->{substr($key, 3)} = $val;
                } else {
                    $variation->{substr($key, 3)} = $val;
                }
            }
            if(!isset($bases[$rec->b__id]->variations)){
                $bases[$rec->b__id]->variations = [];
            }
            $bases[$rec->b__id]->variations[] = $variation;
        }
        $this->result = $bases;
    }

    protected function handleAssocVariations()
    {
        foreach($this->result as $id => &$item){
            //dd($item);
            $item->assoc_variations = [];
            foreach($item->variations as $variation){
                $item->assoc_variations[$variation->channel_id][$variation->locale_id] = $variation;
            }
        }
    }

    /************************************************************************/
    /************************ Service & initialize **************************/
    /************************************************************************/

    /**
     * Initialize
     *
     * @return void
     */
    protected function initialize()
    {
        $this->config = (object)[];

        $this->setConfig('module', $this->module);

        if(!$this->package){
            $this->package = new Package($this->module);
        }

        $this->setConfig('scheme', $this->package->scheme);

        $this->setConfig('table', $this->scheme->getTable());
        $this->setConfig('variations_table', $this->scheme->getVariationsTable());

        $this->setConfig('operands', ['=', '!=', '>', '<', '>=', '<=', '<>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN']);
        $this->setConfig('base_prefixes', ['b', 'base', $this->table]);

        $variation_prefixes = ['v', 'variation', 'var', 'variations',];
        if($this->variations_table){
            $variation_prefixes[] = $this->variations_table;
        }
        $this->setConfig('variation_prefixes', $variation_prefixes);

        $this->setConfig('log_name', 'omni_package_manager');
        $this->setConfig('log_level', 1);

        if(!Chlo::isInstance()){
            Chlo::init();
        }
    }

    protected function setConfig($key, $value)
    {
        $this->config->{$key} = $this->{$key} = $value;
    }

    protected function setRequestConfig($key, $value)
    {
        $this->request_config[$key] = $value;
    }

    protected function getRequestConfig($key)
    {
        return !empty($this->request_config[$key]) ? $this->request_config[$key] : null;
    }

    public function setQBMode()
    {
        $this->qbm = true;
    }

    public function cancelQBMode()
    {
        $this->qbm = false;
    }

    protected function getRelation()
    {
        //dump('$this->with: ',$this->with);

        self::$test_mode && dump('-------------------------------------relations/images-------------------------------------------');
        foreach($this->with as $key => $val){
            //dump('>>>>> ',$key, $val, '<<<<');
            if($key != 'variations'){
                $options = ($val && $val !== true) ? $val : [];
                //dump($key, $options);
                $this->resolveWith($key, $options);
            }
        }

        if(self::$test_mode){
            // dump('$this->relations: ', $this->relations);
            foreach($this->relations as $relation){
                $relation->info();
            }
        }

        if(!empty($this->relations)){
            foreach($this->relations as $relation){
                $records = $relation->isBase() ? $this->base_records : $this->variation_records;
                $relationHandler = RelationAdapter::getHandler($relation, $this->package);
                $relationHandler->attachRelatedRecords($records);
            }
        }

        self::$test_mode && dump('------------- END -------------------relations/images-------------------------------------------');
    }

    protected function resolveWith($with, $options)
    {
        //dump('resolveWith() $with: '. $with);
        $relations = $this->scheme->getRelation($with, options: $options);
        //dump($relations);
        $this->relations = array_merge($this->relations, $relations);

    }

    protected function getRelationList($fields = false, $full = false)
    {
        $this->get();
        if(!$full){
            $this->customizeResult($fields);
        }
        return $this->base_records;
    }


    protected function customizeResult($fields)
    {
        if($this->package){
            $customMethodUsed = $this->tryCustomMethod();
        }

        $this->adaptRelatedList($customMethodUsed, $fields);

    }

    protected function tryCustomMethod()
    {
        $method = 'getRelationList';

        $path = $this->package->getPaths();
        if($path){
            require_once $path['repository_path'];
            $class_name = $path['class'];
            $class = new $class_name($this->package);
            if(method_exists($class, $method)){
                $class->$method($this);
                return true;
            }
        }
        return false;
    }

    protected function tryCustomMethod_OLD()
    {
        $method = 'getRelationList';
        $dirs = $this->package->getPackageFolders();
        if(!empty($dirs)){
            foreach($dirs as $dir){
                $class_name = $this->package->getPackageName();
                $class_path = base_path($dir . '/Libraries/' . $class_name.'.php');
                // dd($class_path);
                if(file_exists($class_path)){
                    Manager::$test_mode && dump('$file_exists: '.$class_path);
                    require_once $class_path;
                    $class = new $class_name();
                    if(method_exists($class, $method)){
                        Manager::$test_mode && dump('method_exists: '.$method);
                        $class->$method($this);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function adaptRelatedList(bool $customMethodUsed, array|false $fields = false)
    {
        $must_included_fields = [
            'key',
            'url_key',
        ];

        if(!empty($this->base_records)){
            if($fields){
                $_fields = [];
                foreach($fields as $k => $name){
                    $_fields[] = $name;
                }

                $must_included_fields = array_merge($_fields, $must_included_fields);
            }

            $listFieldsProvided = false;
            if($this->package->relatedListFields()){
                $listFieldsProvided = true;
                //dump($this->package->relatedListFields());
                $must_included_fields = array_merge($this->package->relatedListFields(), $must_included_fields);
                foreach($must_included_fields as $key => $alias){
                    if(is_numeric($key)){
                        $key = $alias;
                    }
                    $fields[$key] = $alias;
                }

                $computed = [];
                foreach($this->base_records as $record){
                    $_computed = [];
                    foreach($fields as $key => $alias){
                        if(isset($record->{$key})){
                            $_computed[$alias] = $record->{$key};
                        }
                    }
                    $computed[] = (object)$_computed;
                }

                if($order = $this->package->relatedListOrder()){
                    usort($computed, function($a, $b) use ($order){
                        return strtolower($order[1]) == 'asc' ? $a->{$order[0]} <=> $b->{$order[0]} : $b->{$order[0]} <=> $a->{$order[0]};
                    });
                }
            }

            if(!$customMethodUsed && !$listFieldsProvided){
                $computed = [];
                foreach($this->base_records as $record){
                    $title = '';
                    if(!empty($record->title)){
                        $title = $record->title;
                    }

                    if(!$title && !empty($record->name)){
                        $title = $record->name;
                    }

                    if(!$title && !empty($record->description)){
                        $title = mb_substr(strip_tags($record->description), 0, 30).'...';
                    }

                    if(!$title){
                        $title = $record->id;
                    }

                    $_computed = [
                        'id' => $record->id,
                        'title' => $title
                    ];

                    foreach($must_included_fields as $name){
                        if(isset($record->{$name})){
                            $_computed[$name] = $record->{$name};
                        }
                    }

                    $computed[] = (object)$_computed;
                }

            }

            if(!empty($computed)){
                $this->base_records = $computed;
            }
        }

        return $this->base_records;

    }

    /**** END ***************************************************************/

    // public function getEditingItem($id = false)
    // {
    //     self::$test_mode && dump('START geting item for ' . $this->module . ' record for admin');

    //     if ($id) {
    //         $this->where('id', $id);
    //     }

    //     self::$test_mode && dump('Condition for found item: ', $this->wheres);

    //     $this->config('nested_variations', 'assoc_variations');

    //     $editingFields = $this->package->getAllEditingFields();

    //     self::$test_mode && dump('$editingFields: ', $editingFields);

    //     foreach ($editingFields as $key => $field) {

    //         if ($field->type == 'image' || $field->type == 'related') {
    //             $this->with($key);
    //         }
    //     }

    //     self::$test_mode && dump('$this->with: ', $this->with);

    //     $this->getBaseRecords();
    //     if (!$this->base_records) {
    //         return false;
    //     }

    //     $this->base_records = [reset($this->base_records)];

    //     //dump('$this->base_records: ', $this->base_records);

    //     //$this->ids['base'] = collect($this->base_records)->pluck('id')->toArray();

    //     $this->tryGetVariations();

    //     self::$test_mode && dump('>>> RESULT >>> : ', $this->base_records[0]);

    //     $this->getRelation();

    //     return $this->base_records[0];
    // }

    public function getEditingItem($id = false)
    {
        self::$test_mode && dump('START geting item for ' . $this->module . ' record for admin');

        if ($id) {
            $this->where('id', $id);
        }

        self::$test_mode && dump('Condition for found item: ', $this->wheres);

        $this->config('nested_variations', 'assoc_variations');

        $editingFields = $this->package->getAllEditingFields();

        self::$test_mode && dump('$editingFields: ', $editingFields);

        foreach ($editingFields as $key => $field) {

            if ($field->type == 'image' || $field->type == 'related') {
                $this->with($key);
            }
        }

        self::$test_mode && dump('$this->with: ', $this->with);

        $this->getBaseRecords();
        if (!$this->base_records) {
            return false;
        }

        $this->base_records = [reset($this->base_records)];

        //dump('$this->base_records: ', $this->base_records);

        //$this->ids['base'] = collect($this->base_records)->pluck('id')->toArray();

        $this->tryGetVariations();

        self::$test_mode && dump('>>> RESULT >>> : ', $this->base_records[0]);

        $this->getRelation();

        return $this->base_records[0];
    }

    /**** UNUSED ************************************************************/




}

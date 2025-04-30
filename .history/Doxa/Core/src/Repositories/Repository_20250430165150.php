<?php

namespace Doxa\Core\Repositories;

use ReflectionClass;
use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Libraries\Package\Package;
use Doxa\Admin\Repositories\OmniRepositoryTrait;
use Doxa\Core\Libraries\ModuleManager\Manager as ModuleManager;

class Repository
{
    use OmniRepositoryTrait;

    protected $item;

    protected $with = [];

    protected $errors = [];

    protected $table;

    protected $module = '';

    protected ?Package $package = null;

    public function __construct($package = null) 
    {
        dd($package, $this->module);

        if(!$package && !$this->module){
            die(get_called_class() . ' - not package nor module not provided');
            
        }
        if($this->module){
            $this->package = new Package($this->module);
        } else {
            $this->package = $package;
        }

        $this->table = $this->package->scheme->getTable();
    }

    public function docs()
    {
        $module = $this->package->module;
        $data = $this->getDocsData();
        return view('doxa-admin::omni.docs', ['module' => $module, 'data' => $data, 'src' => 'docs']);
    }

    protected function getDocsData()
    {
        $childClassPath = $this->getChildClassDir();

        $docPath = $childClassPath . '/Docs/' . app()->getLocale() . '.php';

        // later with omni trans
        return file_exists($docPath) ? file_get_contents($docPath) : 'No documentation provided for this package';
    }

    protected function getChildClassDir(): string
    {
        $childClass = get_called_class();
        $reflection = new ReflectionClass($childClass);
        return dirname($reflection->getFileName(), 2);
    }

    public function getHistory()
    {
        return $this->mm->getHistory($this->item->id);
    }

    public function afterSave()
    {

    }

    public function checkItemExists($id)
    {
        return DB::table($this->package->scheme->getTable())->where('id', $id)->first();
    }

    public function find($id)
    {
        $item =  DB::table($this->package->scheme->getTable())->where('id', $id)->first();
        return $item;

    }

    public function getEditedItem(int $id)
    {
        return $this->item = $this->mm->where('id', $id)->getEditingItem();
    }

    public function getActualData()
    {
        $data = [];
        foreach ($this->package->getEditingFields() as $key => $field) {
            $data[$key] = !$this->item ? '' : $this->item->{$key};
        }
        if ($this->package->hasVariations()) {
            foreach (Chlo::altAsAssocById() as $channel_id => $channel) {
                foreach ($channel->locales as $locale_id => $locale) {
                    foreach ($this->package->getEditingVariationFields() as $key => $field) {
                        $data['variation'][$channel_id][$locale_id][$key] =
                            !$this->item ? '' : (isset($this->item->assoc_variations[$channel_id][$locale_id]) ? $this->item->assoc_variations[$channel_id][$locale_id]->$key : '');
                    }
                }
            }
        }
        return $data;
    }

    public function with($data)
    {
        if(is_array($data)) {
            $this->with = array_merge($this->with, $data);
        } else {
            $this->with[] = $data;
        }
        return $this;
    }

    public function withContains($name)
    {
        if(empty($this->with)) {
            return false;
        }
        return in_array($name, $this->with);
    }

    public function __get($property)
    {
        switch ($property) {
            case 'package':
                return $this->package;
            case 'manager':
            case 'mm':  
                $manager = new ModuleManager(
                    $this->package->module,
                    package: $this->package
                );
                return $manager;   
            case 'qb':
                $manager = new ModuleManager(
                    $this->module,
                    package: $this->package
                );
                $manager->setQBMode();
                return $manager;      
            default:
                return false;
        }
    }

    // protected function setError($error)
    // {
    //     $this->errors[] = $error;
    // }

    // public function getErrors()
    // {
    //     return implode(', ', $this->errors);
    // }

    // public function isError()
    // {
    //     return !empty($this->errors);
    // }

    /**
     * Undocumented function
     *
     */
    public static function init()
    {
        $name = get_called_class();
        return new $name();
    }
}

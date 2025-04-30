<?php

namespace Doxa\Modules\Notification\Repositories;

use Doxa\Core\Repositories\Repository;

class Notification extends Repository
{
    protected $module = 'notification';

    //protected int $cache_expire = 60 * 24 * 365;

    // public function afterSave($params = [])
    // {
    //     $item = $this->mm->with('variations')->where($params)->config('nested_variations')->first();

    //     foreach($item->variations as $variation){
    //         $key = 'notification.' . Chlo::getLocaleCodeById($variation->locale_id). '.' . $item->key;
    //         Cache::put($key, $variation->text, $this->cache_expire);
    //     }
    // }

    public function getByKey($key, $replace = [])
    {
        return $this->getBy(['key' => $key], $replace);
    }

    public function getById($id, $replace = [])
    {
        return $this->getBy(['id' => $id], $replace);
    }

    public function getBy($where, $replace = [])
    {
        $where['active'] = 1;
        $item = $this->mm->where($where)->with('variations')->first();
        if($item){
            if(!empty($replace)){
                foreach($replace as $key => $value){
                    $item->text = str_replace('{' . $key . '}', $value, $item->text);
                    $item->title = str_replace('{' . $key . '}', $value, $item->title);
                }
            }
        }
        return $item;
    }

    public static function init()
    {
        return new Notification();
    }
}

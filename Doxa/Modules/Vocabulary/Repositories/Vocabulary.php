<?php

namespace Doxa\Modules\Vocabulary\Repositories;

use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Doxa\Core\Repositories\Repository;

class Vocabulary extends Repository
{
    protected int $cache_expire = 60 * 24 * 365;

    public function afterSave($params = [])
    {
        $item = $this->mm->with('variations')->where($params)->config('nested_variations')->first();

        foreach($item->variations as $variation){
            $key = 'vocabulary.' . Chlo::getLocaleCodeById($variation->locale_id). '.' . $item->key;
            Cache::put($key, $variation->text, $this->cache_expire);
        }
    }
}

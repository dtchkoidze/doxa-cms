<?php

namespace Doxa\Modules\TextBlock\Repositories;

use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Doxa\Core\Repositories\Repository;

class TextBlock extends Repository
{
    protected int $cache_expire = 60 * 24 * 365;

    public function afterSave($params = [])
    {
        $item = $this->mm->with('text_blocks')->where($params)->config('nested_variations')->first();

        foreach ($item->variations as $variation) {
            $key = 'text_blocks.' . Chlo::getLocaleCodeById($variation->locale_id) . '.' . $item->key;
            Cache::put($key, $variation->text, $this->cache_expire);
        }
    }

    public function getItemsStartingWith(string $prefix)
    {




        $items = DB::select("
    SELECT 
        SUBSTRING_INDEX(tbv.text, '//', 1) AS title,
        SUBSTRING_INDEX(tbv.text, '//', -1) AS description
    FROM text_block_variations AS tbv
    JOIN text_blocks AS tb ON tbv.src_id = tb.id
    WHERE tbv.locale_id = :locale_id
    AND tb.key LIKE CONCAT(:prefix, '%')
", ['locale_id' => Chlo::getCurrentLocaleId(),  'prefix' => $prefix]);


        // dd($items);
        return $items;
    }
}

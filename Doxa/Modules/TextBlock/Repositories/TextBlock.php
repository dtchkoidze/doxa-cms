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

    /**
     * Возвращает text blocks по префиксу ключа: title/description из text (разделитель //).
     * Разбор строки в PHP — одинаково для MySQL и PostgreSQL.
     *
     * @param string $prefix префикс ключа text_blocks.key
     * @return array<int, object{title: string, description: string}>
     */
    public function getItemsStartingWith(string $prefix)
    {
        $rows = DB::table('text_block_variations as tbv')
            ->join('text_blocks as tb', 'tbv.src_id', '=', 'tb.id')
            ->where('tbv.locale_id', Chlo::getCurrentLocaleId())
            ->where('tb.key', 'like', $prefix . '%')
            ->get(['tbv.text']);

        $items = [];
        foreach ($rows as $row) {
            $text = (string) $row->text;
            // как MySQL SUBSTRING_INDEX(..., '//', 1) и SUBSTRING_INDEX(..., '//', -1)
            $parts = explode('//', $text);
            $items[] = (object) [
                'title' => $parts[0],
                'description' => $parts[count($parts) - 1],
            ];
        }

        return $items;
    }
}

<?php

namespace Doxa\Core\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Dictionary
{

    public static function create(array $keys): array
    {
        $locales =DB::table('locales')->pluck('code', 'id')->toArray();
        //dump('$locales', $locales);

        
        //dump($keys);

        $variations_map = [
            'vocabulary' => 'vocabulary_variations',
            'text_blocks' => 'text_block_variations',
        ];

        $dictionary = [];

        foreach ($keys as $tb => $arr) {
            $variations_by_src_locale = [];
            
            $base_records = DB::table($tb)->whereIn('key', $arr)->get();
            $base_ids = [];
            if($base_records){
                $base_ids = $base_records->pluck('id')->toArray();
            }
            $base_records = $base_records->pluck('key', 'id')->toArray();

            if(!empty($base_records)) {
                $variations = DB::table($variations_map[$tb])->whereIn('src_id', $base_ids)->get()->toArray();
                //dump('$variations', $variations);
                if(!empty($variations)){
                    foreach($variations as $variation) {
                        $dictionary[$locales[$variation->locale_id]][$tb][$base_records[$variation->src_id]] = $variation->text;
                    }
                    
                }
            } 

            //dump('--$dictionary', $dictionary);

            //dump($src, $base_records, $base_ids, $variations);
            // dump([
            //     '$tb' => $tb, 
            //     '$arr' => $arr, 
            //     '$base_records' => $base_records, 
            //     '$base_ids' => $base_ids, 
            //     '$variations' => $variations,
            //     '$variations_by_src_locale' => $variations_by_src_locale,
            // ]);
            
            foreach($arr as $key) {
                foreach($locales as $id => $code) {
                    if(empty($dictionary[$code][$tb][$key])) {
                        $dictionary[$code][$tb][$key] = $tb.'.'.$key;
                    }
                }
            }

            //dump('>>> $variations_by_src_locale', $variations_by_src_locale);
            //$dictionary[$tb] = $variations_by_src_locale;

        }

        return $dictionary;

        //dump('$dictionary', $dictionary);

    }
}

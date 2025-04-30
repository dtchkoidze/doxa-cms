<?php

namespace Doxa\Modules\StaticPage\Repositories;

use Doxa\Core\Libraries\ModuleManager\Manager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Doxa\Core\Repositories\Repository;

class StaticPage extends Repository
{
    protected $module = 'static_page';

    public function getUrlKeys()
    {
        $keys = Cache::rememberForever('static_page_url_keys', function () {
            $static_pages = Manager::pm('static_page')->get();

            $keys = [];

            foreach ($static_pages as $page) {
                $keys[] = $page->url_key;
            }

            $keys = implode('|', $keys);

            return $keys;
        });

        return $keys;
    }

    public function getByUrlKey($key)
    {
        return $this->mm->locale('current')->channel('current')->where('url_key', $key)->first();
        //return DB::table($this->package->scheme->getTable())->where('url_key', $key)->first();
    }
}

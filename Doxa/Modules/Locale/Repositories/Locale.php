<?php

namespace Doxa\Modules\Locale\Repositories;

use Illuminate\Support\Facades\DB;
use Doxa\Core\Repositories\Repository;

class Locale extends Repository
{
    public function getList()
    {
        return DB::table($this->package->scheme->getTable())->get()->toArray();
    }
}

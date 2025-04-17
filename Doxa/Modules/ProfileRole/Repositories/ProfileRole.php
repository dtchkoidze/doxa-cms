<?php

namespace Doxa\Modules\ProfileRole\Repositories;

use Doxa\Core\Libraries\Chlo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Doxa\Core\Repositories\Repository;

class ProfileRole extends Repository
{
    public function getListForRegistration()
    {
        $list = $this->mm->where('active', 1)->orderBy('position', 'asc')->get();
        return $list;
    }

    public function getNameById($id)
    {
        $rec = $this->mm->where('id', $id)->first();
        return $rec->name;
    }

    public function getIdByName($name)
    {
        $rec = $this->mm->where('name', $name)->first();
        return $rec->id;
    }
}

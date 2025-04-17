<?php

namespace Doxa\Modules\User\Repositories;

use Illuminate\Support\Facades\DB;
use Doxa\Core\Repositories\Repository;

class User extends Repository
{
    public function getById($id)
    {
        return $this->mm->where('id', $id)->first();
    }
}

<?php

namespace Doxa\Modules\Channel\Repositories;

use Illuminate\Support\Facades\DB;
use Doxa\Core\Repositories\Repository;

class Channel extends Repository
{
    public function getCurrentChannel()
    {
        $hostname = request()->getHttpHost();

        $currentChannel = $this->mm->with('locales')->where('host', 'in', [
            "'".$hostname."'",
            "'".'http://' . $hostname."'",
            "'".'https://' . $hostname."'",
        ])->first();

        return $currentChannel;
    }
}

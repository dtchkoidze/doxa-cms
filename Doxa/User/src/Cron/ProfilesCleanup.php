<?php

namespace Doxa\User\Cron;

use Illuminate\Support\Facades\DB;
use Doxa\Core\Libraries\Logging\Clog;

class ProfilesCleanup
{

    public function run()
    {
        $users = DB::table('users')
            ->select('id')
            ->get()
            ->pluck('id')
            ->toArray();

        Clog::write('users', 'Users in DB: '.sizeof($users), 4); 

        $profile_to_clean = DB::table('user_profiles')->whereNotIn('user', $users)->get()->pluck('id')->toArray();
        Clog::write('users', 'Profiles cleanup: '.sizeof($profile_to_clean), 4);    

        if($profile_to_clean){
            DB::table('user_profiles')->whereNotIn('user', $users)->delete();
        }
        

    }

    public function __invoke()
    {
        $this->run();
    }
}

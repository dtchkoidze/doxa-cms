<?php

namespace Doxa\User\Cron;

use Illuminate\Support\Facades\DB;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Libraries\Registration;

class NotConfirmedUsers
{
    public function run()
    {
        $inactivity = now()->subHours(24);
        //dd($inactivity);

        $not_confirmed_users = DB::table('users')
            ->select('id')
            ->where('updated_at', '<', $inactivity)
            ->whereIn('status',[Registration::VERIFICATION_PENDING_STATUS, Registration::PASSWORD_PENDING_STATUS])
            ->get()
            ->pluck('id')
            ->toArray();

        if($not_confirmed_users){
            Clog::write('users', '24h not confirmed users: '.sizeof($not_confirmed_users), 4); 
            DB::table('users')->whereIn('id', $not_confirmed_users)->delete();
            DB::table('user_profiles')->whereIn('user', $not_confirmed_users)->delete();
        } else {
            Clog::write('users', '24h not confirmed users not found.', 4); 
        }  

    }

    public function __invoke()
    {
        $this->run();
    }
}

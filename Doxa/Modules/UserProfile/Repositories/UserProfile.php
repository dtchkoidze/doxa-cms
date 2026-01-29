<?php

namespace Doxa\Modules\UserProfile\Repositories;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Repositories\Repository;
use Doxa\Core\Libraries\Package\Package;

class UserProfile extends Repository
{
    public $user = null;

    public $profile = null;

    public function initialize($user)
    {
        if (is_int($user)) {
            $this->user = DB::table('users')->where('id', $user)->first();
        } else {
            $this->user = $user;
        }

        $this->profile = $this->user->profile = $this->mm->where('user', $this->user->id)->with(['role'])->first();

        return $this;
    }

    public function create($user, $role = 0)
    {
        $set = [
            'hash' => strtoupper(Str::random(3)),
            'user' => $user->id,
            'status' => 1,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('user_profiles')->insert($set);
    }

    public static function init()
    {
        return new UserProfile();
    }
}

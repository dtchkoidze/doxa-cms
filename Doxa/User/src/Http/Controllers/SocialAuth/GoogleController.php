<?php

namespace Doxa\User\Http\Controllers\SocialAuth;

use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        dd('handleGoogleCallback');
    }
}

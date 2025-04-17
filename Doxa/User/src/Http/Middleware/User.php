<?php

namespace Doxa\User\Http\Middleware;

use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class User
{
    private $request;

    private $guard = 'web';

    private $user = null;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = 'web')
    {
        //dd('user middleware');

        $this->request = $request;
        $this->guard = $guard;

        // if (auth()->user() && auth()->user()->admin) {
        //     return redirect()->route('admin.dashboard');
        // }

        $request->merge(['user' => true]);

        if(!auth()->user()){
            return redirect(route('user.login'));
        } else {
            if(auth()->user()->isSuspended()){
                return redirect()->route('user.suspended');
            } else {
                if(!auth()->user()->isActive()){
                    if(!auth()->user()->isVerifed()){
                        return redirect()->route('user.verify');
                    } else {
                        dd('user verifed but not active, wrong logic');
                    }
                } else {
                    return $next($request);
                }
            }
        }    


        // switch (Route::currentRouteName()) {
        //     case 'user.home':
        //         return redirect()->route('user.dashboard');

        //     case 'user.register':
        //     case 'user.login':
        //     case 'user.register.store':
        //     case 'user.login.store':
        //     case 'user.logout':
        //     case 'user.verify':
        //     case 'user.verification.by_code':
        //     case 'user.verification.link':
        //     case 'user.resend_verification_code':
        //     case 'user.verification_code_expired':
        //     case 'user.wrong_verification_token':
        //     case 'user.auth.check':
        //     case 'dusty.home.index':
        //     case 'dusty.point':
        //     case 'dusty.qr':
        //         return $next($request);

        //     default:
        //         if (!Auth::check() && !Auth::viaRemember()) {
        //             return redirect(route('user.login'));
        //         } else {
        //             $this->user = UserModel::find(Auth::user()->id);
        //             if (!$this->user->isVerifed()) {
        //                 return redirect()->route('user.verify');
        //             }
        //         }


        //         return $next($request);
        // }
    }
}

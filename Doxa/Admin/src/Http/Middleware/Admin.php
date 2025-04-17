<?php

namespace Doxa\Admin\Http\Middleware;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Route;

class Admin
{
    private $request;

    private $guard = 'web';

    private $user = null;

    const LOGIN_ROUTE_NAME = 'admin.login';

    const REGISTER_ROUTE_NAME = 'admin.register';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = 'web')
    {

        //Clog::write('admin', '*');
        //Clog::write('admin', '--------MIDLEWARE START-------'.Route::currentRouteName().'---------------');

        $this->request = $request;
        $this->guard = $guard;

        $request->merge(['admin' => true]);

        if(!auth()->user()){
            return redirect(route('auth.login').'?mode=admin');
        } else {
            if(!auth()->user()->isAdmin()){
                Auth::logout();
                return redirect(route('auth.login').'?mode=admin');
            }
            if(auth()->user()->isSuspended()){
                return redirect()->route('auth.suspended');
            } else {
                if(!auth()->user()->isActive()){
                    if(auth()->user()->isVerifed()){
                        return redirect()->route('auth.waiting_for_activate');
                    } else {
                        return redirect()->route('auth.verify');
                    }
                } else {

                    // TODO check if has any permission

                    return $next($request);
                }
            }
        }

    }

}

<?php

namespace Doxa\User\Http\Middleware;

use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use Doxa\User\Libraries\Registration;

class Authorization
{
    private $request;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        Clog::write(Registration::LOG, '>>>>>>>>>>>>>>>> MIDDLEWARE::Registration, route: ' . Route::currentRouteName() . ', >>>>>>>>>>>>>>>>>>>', Clog::NOTICE);

        $registration = Registration::init();

        Clog::write(Registration::LOG, 'Route::currentRouteName(): ' . Route::currentRouteName(), Clog::NOTICE);

        /**
         * Check GET params for verification link, v_hash(vh) and login_type(lt) must be provided.
         * If not, we clear auth cookie, logout and redirect to error page.
         */
        switch (Route::currentRouteName()) {
            case 'auth.verification_link':
                if (!request('vh') || !request('lt')) {
                    Clog::write('auth', '---- wrong-verification-link', Clog::DEBUG);
                    $this->clear();
                    return $this->_responce('auth.error', ['error' => 'wrong-verification-link']);
                }
                break;
        }

        switch (Route::currentRouteName()) {

            case 'auth.recovery':
                if (Auth::check()) {
                    return redirect(Registration::getSuccessAuthUrl());
                } else {
                    $this->clear();
                }
                break;
            case 'auth.register':
            case 'auth.login':
                if (Auth::check()) {
                    $this->clear();
                }    
                break;
        }

        switch (Route::currentRouteName()) {

            // routes bellow are allowed for all users and must have no cookies and no auth usere, so clear cookies and logout
            case 'auth.login':
            case 'auth.register':
            case 'auth.recovery':
            case 'auth.error':
            case 'auth.api.login':
            case 'auth.api.register':
                $this->clear();
                break;

            // --------- see log messages    
            case 'auth.waiting_for_activate':
                if (Auth::check() || !Registration::user() || !Registration::vhash()) {
                    if (Auth::check()) {
                        Clog::write('auth', 'User is authenticated!! but this route is not for authenticated users!', Clog::ERROR);
                    }
                    if (!Registration::user()) {
                        Clog::write('auth', 'User not found!', Clog::ERROR);
                    }
                    if (!Registration::vhash()) {
                        Clog::write('auth', 'Vhash not defined! Manually deleted?', Clog::ERROR);
                    }
                    //Auth::check() && Clog::write('auth', 'User is authenticated!! but this route is not for authenticated users!', Clog::ERROR);
                    //!Registration::user() && Clog::write('auth', 'User not found!', Clog::ERROR);
                    //!Registration::vhash() && Clog::write('auth', 'Vhash not defined! Manually deleted?', Clog::ERROR);
                    $this->clear();
                    return $this->_responce('auth.error', ['error' => 'session-expired']);
                }
                break;


            case 'auth.verify':
            case 'auth.password':
            case 'auth.verification_link':
                if (Auth::check()) {
                    return redirect(Registration::getSuccessAuthUrl());
                } else {
                    if (!Registration::user() || !Registration::vhash()) {
                        if (!Registration::user()) {
                            Clog::write('auth', 'User not found!', Clog::ERROR);
                        }
                        if (!Registration::vhash()) {
                            Clog::write('auth', 'Vhash not defined! Manually deleted?', Clog::ERROR);
                        }
                        $this->clear();
                        return $this->_responce('auth.error', ['error' => 'session-expired']);
                    }
                }
                break;


            case 'auth.api.resend_verification_code':
            case 'auth.api.verification_by_code':
            case 'auth.api.set_password':
                Clog::write(Registration::LOG, 'Registration::method(): ' . Registration::method(), Clog::NOTICE);

                Clog::write(Registration::LOG, 'Registration::user(): ' . Registration::user(), Clog::NOTICE);
                switch (Registration::method()) {
                    case 'account_deletion':
                        if (!Auth::check()) {
                            return response()->json(collect(['redirect' => '/my-profile']));
                        }
                        break;
                    default:
                        if (Auth::check() || !Registration::user() || !Registration::vhash()) {
                            if (Auth::check()) {
                                Clog::write('auth', 'User is authenticated!! but this route is not for authenticated users!', Clog::ERROR);
                            }
                            if (!Registration::user()) {
                                Clog::write('auth', 'User not found!', Clog::ERROR);
                            }
                            if (!Registration::vhash()) {
                                Clog::write('auth', 'Vhash not defined! Manually deleted?', Clog::ERROR);
                            }
                            $this->clear();
                            return $this->_responce('auth.error', ['error' => 'session-expired']);
                        }

                        if (!Registration::isValidMethod()) {
                            Clog::write('auth', Registration::method() . ' not exists! Redirect to error/invalid-method', Clog::DEBUG);
                            $this->clear();
                            return $this->_responce('auth.error', ['error' => 'invalid-method']);
                        }
                        break;
                }
                break;
        }

        //Clog::write('auth', 'MIDDLEWARE 002', Clog::NOTICE);

        /**
         * Check active
         */
        switch (Route::currentRouteName()) {
            case 'auth.verify':
            case 'auth.verification_link':
            case 'auth.api.resend_verification_code':
            case 'auth.api.verification_by_code':
            case 'auth.password':
            case 'auth.api.set_password':
                //Clog::write('auth', 'MIDDLEWARE 002-001', Clog::NOTICE);
                switch (Registration::method()) {
                    case 'register':
                        //Clog::write('auth', 'MIDDLEWARE 002-001-register', Clog::NOTICE);
                        if (Registration::user()->isActive()) {
                            Clog::write('auth', 'ERROR: Verify page but user is active! Redirect to login.', Clog::ERROR);
                            $this->clear();
                            return $this->_responce('auth.login');
                        }
                        break;
                    case 'recovery':
                        //Clog::write('auth', 'MIDDLEWARE 002-001-recovery', Clog::NOTICE);
                        if (!Registration::user()->isActive()) {
                            Clog::write('auth', 'ERROR: User ' . Registration::login() . ' requests recovery link but not active!! .', Clog::ERROR);
                            $this->clear();
                            return $this->_responce('auth.error', ['error' => 'session-expired']);
                        }
                        break;
                }
                break;
        }

        //Clog::write('auth', 'MIDDLEWARE 003', Clog::NOTICE);

        /**
         * check status
         */
        switch (Route::currentRouteName()) {
            case 'auth.verify':
            case 'auth.api.resend_verification_code':
            case 'auth.api.verification_by_code':
            case 'auth.verification_link':
                switch (Registration::method()) {
                    case 'account_deletion':
                        if (!Auth::check()) {
                            return response()->json(collect(['redirect' => '/my-profile']));
                        }
                        $registration->login_type = 'email';
                        $registration->login = Auth::user()->email;
                        $registration->getUserById(Auth::id());
                        break;
                    default:
                        if (Route::currentRouteName() == 'auth.verify' && !Registration::user()->hasVericationPendingStatus()) {
                            Registration::setVerificationdPendingStatus();
                        }
                        if (!Registration::user()->hasVericationPendingStatus()) {
                            Clog::write('auth', 'ERROR: Verify page but user status is wrong: ' . Registration::user()->getStatus(), Clog::ERROR);
                            $this->clear();
                            return $this->_responce('auth.login');
                        }
                        break;
                }
                break;
        }

        /**
         * check password pending status
         */
        switch (Route::currentRouteName()) {
            case 'auth.password':
            case 'auth.api.set_password':
                if (!Registration::user()->hasPasswordPendingStatus()) {
                    Clog::write('auth', 'ERROR: Password page but user status is wrong: ' . Registration::user()->getStatus(), Clog::ERROR);
                    $this->clear();
                    return $this->_responce('auth.login');
                }
                break;
        }

        return $next($request);
    }

    protected function clear()
    {
        Auth::logout();
        Registration::clearAuthCookie();
    }

    protected function _responce($route, $data = [])
    {
        if (request()->ajax()) {
            return response()->json(collect(['redirect' => route($route, $data),]));
        } else {
            return redirect()->route($route, $data);
        }
    }
}

<?php

namespace Doxa\User\Http\Controllers\Registration;

use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Libraries\Registration as REG;
use Illuminate\Support\Facades\Cookie;

class RegistrationController extends Controller
{
    use ResponceTrait;

    /**
     * Login page
     *
     * @return View
     */
    public function login()
    {
        Clog::write(REG::LOG, 'Controller::Login', Clog::DEBUG);

        //dd(request()->all());

        return view('user::auth.login')->with([
            'wrapper' => REG::authWrapper(),
            'title' => vocab('log_in'),
        ]);
    }

    /**
     * Registration page
     *
     * @return View
     */
    public function register(): View
    {
        Clog::write(REG::LOG, 'Registration page', Clog::DEBUG);
        
        return view('user::auth.register', [
            'roles' => REG::profileRoles(),
            'wrapper' => REG::authWrapper(),
            'title' => vocab('register'),
        ]);
    }

    /**
     * Recovery page
     *
     * @return View
     */
    public function recovery(): View
    {
        Clog::write(REG::LOG, 'Recovery page', Clog::DEBUG);
        
        return view('user::auth.recovery', [
            'wrapper' => REG::authWrapper(),
            'title' => vocab('user_recovery_title'),
        ]);
    }

    /**
     * Verification page
     *
     * @return View
     */
    public function verify($method)
    {
        Clog::write(REG::LOG, 'Verify page', Clog::DEBUG);

        $data = [
            'timer' => REG::getResendCodeTimer(),
            'login' => REG::login(),
            'login_type' => REG::loginType(),
            'code_expire_in' => REG::codeExpireIn(),
            'method' => $method,
            'wrapper' => REG::authWrapper(),
            'title' => $method == 'register' ? vocab('user_verification') : vocab('user_recovery_verification_title'),
        ];

        return view('user::auth.verify', $data);
    }

    /**
     * Password Set up Page
     *
     * @return View
     */
    public function password($method)
    {
        Clog::write(REG::LOG, 'Password page', Clog::DEBUG);

        if( REG::isVerificationCodeExpired()){ 
            return redirect()->route('auth.error', ['error' => 'session-expired']);
        }

        $data = [
            'login' => REG::login(),
            'login_type' => REG::loginType(),
            'wrapper' => REG::authWrapper(),
            'title' => vocab('user_set_password_title'),
            'method' => $method,
        ];

        return view('user::auth.password', $data);
    }

    /**
     * Logout, then redirect to query return (internal path) or back to Referer.
     */
    public function logout()
    {
        REG::logout();

        $target = request('return');
        if (is_string($target) && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
            return redirect()->to($target);
        }

        return redirect()->back();
    }

    /**
     * Account suspended page
     *
     * @return View
     */
    public function suspended(): View
    {
        Clog::write(REG::LOG, 'Suspended page', Clog::DEBUG);

        return view('user::auth.suspended', [
            'wrapper' => REG::authWrapper(),
            'title' => vocab('user_suspended_title'),
        ]);
    }

    /**
     * Waiting for activate page
     * 
     * @return View
     */ 
    public function waitingForActivate()
    {
        if(REG::user()->isActive()){
            Auth::login(REG::user());
            REG::recordLoginArtifacts();
            REG::clearAuthCookie();
            return redirect(REG::getSuccessAuthUrl()); 
        }

        $data = [
            'login' => REG::login(),
            'login_type' => REG::loginType(),
            'wrapper' => REG::authWrapper(),
            'title' => vocab('user_waiting_activation_title'),
        ];

        return view('user::auth.waiting-for-activate', $data);
    }

    public function error($error)
    {
        //Clog::write($this->log_name, 'Error page: '.$error, Clog::DEBUG);

        switch ($error) {
            case 'session-expired':
                $title = vocab('user_session_expired');
                $_error = [
                    'title' => $title,
                    'message' => vocab('user_session_expired_text'),
                ];
                break;
            case 'invalid-method':
                $title = vocab('user_invalid_method_title');
                $_error = [
                    'title' => $title,
                    'message' => vocab('user_invalid_method_text'),
                ];
                break;
            default:
                break;
        }
        
        return view('user::auth.error', [
            'title' => $title,
            'wrapper' => REG::authWrapper(),
            'error' => $_error,
        ]);
    }

}

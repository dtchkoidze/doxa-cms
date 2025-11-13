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
        Clog::write(REG::LOG, 'Login page', Clog::DEBUG);

        return view('user::auth.login')->with([
            'wrapper' => REG::authWrapper(),
            'title' => 'Sign In',
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
            'title' => 'Sign Up',
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
            'title' => 'Recovery',
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
            'title' => $method == 'register' ? 'Verification' : 'Access recovery: Verification',
        ];

        return view('user::auth.verify', $data);
    }

    /**
     * Handle Confirnmation link
     *
     * @return void
     */
    public function verificationLink($method)
    {
        Clog::write(REG::LOG, 'Verification link ('.$method.')', Clog::DEBUG);

        if (REG::isVerificationCodeExpired()) {
            Clog::write(REG::LOG, 'Verification code is expired.', Clog::NOTICE);
            return $this->errorResponce('Verification code is expired.');
        }

        REG::setAuthCookie('password')->setPasswordPendingStatus();

        return redirect(route('auth.password', ['method' => $method]));
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
            'title' => 'Set Up Password',
            'method' => $method,
        ];

        return view('user::auth.password', $data);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->back();
        //return redirect()->route('auth.login');
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
            REG::clearAuthCookie();
            return redirect(REG::getSuccessAuthUrl()); 
        }

        $data = [
            'login' => REG::login(),
            'login_type' => REG::loginType(),
            'wrapper' => REG::authWrapper(),
            'title' => 'Waiting for activate',
        ];

        return view('user::auth.waiting-for-activate', $data);
    }

    public function error($error)
    {
        //Clog::write($this->log_name, 'Error page: '.$error, Clog::DEBUG);

        switch ($error) {
            case 'session-expired':
                $title = 'Session expired';
                $_error = [
                    'title' => 'Session expired',
                    'message' => 'Your session has expired. Please Log In or Sign Up again.',
                ];
                break;
            case 'invalid-method':
                $title = 'Invalid method';
                $_error = [
                    'title' => 'Using invalid method',
                    'message' => 'Backend error. Received invalid method parameter. It\'s a bug. Please let us know about it.',
                ];
                break;
            case 'wrong-verification-link':
                $title = 'Wrong verification link';
                $_error = [
                    'title' => 'Wrong verification link',
                    'message' => 'Your verification link is invalid. Try to resend link.',
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

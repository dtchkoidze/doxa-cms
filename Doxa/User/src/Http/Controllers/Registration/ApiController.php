<?php

namespace Doxa\User\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Libraries\Registration as REG;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;


class ApiController extends Controller
{
    use ResponceTrait;

    public function login()
    {
        Clog::write(REG::LOG, 'Login user by API: '.json_encode(request()->all()), Clog::DEBUG);

        $errors = REG::validateLoginRequest();
        if($errors){
            return $this->errorsResponce($errors);
        }
        
        $remember = request()->boolean('remember');
        
        if (Auth::attempt([
            REG::loginType() => REG::login(),
            'password' => request('password'),
        ], $remember)) {
            Clog::write(REG::LOG, 'Success login!', Clog::DEBUG);
            REG::setUserFromAuth();
            if(!REG::user()->isActive()){
                Auth::logout();
                REG::clearAuthCookie();
                if(REG::user()->hasReadyStatus()){
                    Clog::write(REG::LOG, 'REG::user()->hasReadyStatus()', Clog::DEBUG);
                    REG::setAuthCookie('ready');
                    return $this->accountVerifedAndRedirect([
                        'message' => 'Your account is verified and waiting for activation. By clicking OK you will be redirected to activation status page.',
                        'url' => route('auth.waiting_for_activate'),
                    ]);
                } else {
                    return $this->responceLoginFailed();
                }
            } else {
                return $this->redirectResponce(REG::getSuccessAuthUrl());
            }

        } else {
            return $this->responceLoginFailed();
        }


    }

    /**
     * Register user by API
     * 
     * Middleware:
     * - clear auth cookie
     * - logout
     * 
     * Logic:
     * - validate request
     * - check if user exists
     *      - if exists:
     *          - if user has not finished registration, offer to finish registration
     *          - else return user already registred
     *      - else 
     *          create user
     *          create user profile
     *          send register message
     *          set auth cookie
     *          redirect to verification page    
     * 
     *
     * @return JsonResponce
     */
    public function register()
    {
        Clog::write(REG::LOG, 'Register user by API: '.json_encode(request()->all()), Clog::DEBUG);

        $errors = REG::validateRegistrationRequest();
        if($errors){
            return $this->errorsResponce($errors);
        }

        // >>>>>>>>> USER EXISTS ??
        REG::getUserByLogin();
        if(REG::user()){
            Clog::write(REG::LOG, 'User found: '.REG::login(), Clog::DEBUG);
            if(!REG::user()->isActive() && (REG::user()->hasVericationPendingStatus() || REG::user()->hasPasswordPendingStatus())){
                Clog::write(REG::LOG, 'User has pending status: '.REG::user()->getStatus(), Clog::DEBUG);
                if(REG::user()->hasPasswordPendingStatus()){
                    Clog::write(REG::LOG, 'User has password pending status, will be changed to verification pending.', Clog::DEBUG);
                    REG::setVerificationdPendingStatus();
                }
                REG::setAuthCookie('verify');
                return $this->responceRegistrationInProcess();
            }
            Clog::write(REG::LOG, 'User has not pending status or active, status: '.REG::user()->getStatus().', active: '.REG::user()->isActive().' => return error User exists ',  Clog::DEBUG);
            return $this->responceUserAlreadyRegistred();
        }

        // >> END >> USER EXISTS

        Clog::write(REG::LOG, 'User not found, lets\'s create user, profile and send a verification message to '.REG::login(), Clog::DEBUG);
        REG::createUser()
            ->createUserProfile()
            ->sendMessage('register')
            ->setAuthCookie('verify');

        return $this->redirectResponce(route('auth.verify', ['method' => 'register'])); 
    }

    /**
     * OK
     * Resend verification code api
     *
     * @return JsonResponce
     */
    public function resendVerificationCode($method)
    {
        Clog::write(REG::LOG, 'RegistrationApiController::resendVerificationCode('.$method.')', Clog::DEBUG);

        $timer = REG::getResendCodeTimer();
        if($timer){
            return $this->responceWaitForCodeResendTimer($timer);
        }

        REG::refreshSecret()->sendMessage($method);

        return $this->responceCodeSent($method);
    }

    /**
     * API verification by code
     *
     * @return JsonResponce
     */
    public function verificationByCode($method)
    {
        Clog::write(REG::LOG, 'verificationByCode('.$method.')', Clog::DEBUG);

        // wrong code
        if (REG::user()->secret !== trim(request('verification_code'))) {
            Clog::write(REG::LOG, 'wrong verification code, '. trim(request('verification_code')).', provided', Clog::NOTICE);
            return $this->errorResponce('Wrong verification code.');
        }

        // code expired
        if (REG::isVerificationCodeExpired()) {
            Clog::write(REG::LOG, 'Verification code is expired.', Clog::NOTICE);
            return $this->errorResponce('Verification code is expired.');
        }

        REG::refreshSecret();
        REG::setAuthCookie('password', true);

        REG::setPasswordPendingStatus();

        return $this->redirectResponce(route('auth.password', ['method' => $method]));
    }

    /**
     * OK
     * Set user password
     *
     * @return JsonResponce
     */
    public function setPassword($method)
    {
        Clog::write(REG::LOG, 'setPassword('.$method.')', Clog::DEBUG);

        $validator = Validator::make(request()->all(), [
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            return $this->errorsResponce($validator->errors());
        }

        if($method == 'recovery'){
            if(REG::checkNewPasswordSameAsOld(request('password'))){
                return $this->errorsResponce(['password' => 'New password must be different from old one.']);
            }
        }

        REG::setUserPassword(request('password'));

        if(REG::isAutoActivation()){
            REG::setUserActive();
            Auth::login(REG::user());
            REG::clearAuthCookie();
        } 

        REG::setReadyStatus();

        REG::afterSetPassword();

        return $this->passwordSetResponce($method, $active = REG::isAutoActivation());

    }

    /**
     * Checks if user with specified login exists
     *
     * @return void
     */
    public function checkRecoveryLogin()
    {
        $errors = REG::validateRecoveryLoginRequest();
        if($errors){
            Clog::write(REG::LOG, 'Login ('.REG::loginType().', '.REG::logine().') check error: '.json_encode($errors), Clog::WARNING);
            return $this->errorsResponce($errors);
        }

        REG::getUserByLogin();
        if(REG::user()){
            Clog::write(REG::LOG, 'User found: '.REG::login(), Clog::DEBUG);
            if(REG::user()->isActive()){
                REG::refreshSecret()->sendMessage('recovery')->setAuthCookie('recovery');
                return $this->redirectResponce(route('auth.verify', ['method' => 'recovery']));
            }
        }   
        
        return $this->errorsResponce([ REG::loginType() => 'Registred user not found or not activated or suspended.']);
    }

}

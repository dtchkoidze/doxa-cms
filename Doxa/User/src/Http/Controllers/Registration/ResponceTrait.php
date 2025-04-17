<?php

namespace Doxa\User\Http\Controllers\Registration;

use Doxa\User\Libraries\Registration as REG;

trait ResponceTrait
{
    protected function redirectResponce($url)
    {
        if(request()->ajax()){
            return response()->json(collect([
                'success' => true,
                'redirect' => $url,
            ]));
        } else {
            return redirect()->to($url);
        }
    }

    protected function errorsResponce($errors)
    {
        return response()->json(collect([
            'success' => false,
            'errors' => $errors,
        ]));
    }

    protected function errorResponce($error)
    {
        return response()->json(collect([
            'success' => false,
            'error' => $error,
        ]));
    }

    protected function responceRegistrationInProcess($data = [])
    {
        $confirmation = [
            'type' => 'warning',
            'title' => 'You already started registration process',
            'message' => 'Click Confirm button to continue registration and conirm your email or phone number.',
            'buttons' => [
                ['title' => 'Confirm', 'callback' => 'confirmAccount', 'timer' => REG::getResendCodeTimer()],
                ['title' => 'Cancel', 'type' => 'cancel'],
            ],
        ];

        $confirmation = array_merge($confirmation, $data);

        return response()->json(collect([
            'success' => true,
            'confirmation' => $confirmation,
        ]));
    }

    protected function responceUserAlreadyRegistred()
    {
        return response()->json(collect([
            'success' => false,
            'errors' => [
                'register_failed' => 'User with provided ' . REG::loginType() . ' already exists.',
            ],
        ]));
    }

    protected function responceLoginFailed()
    {
        return response()->json(collect([
            'success' => false,
            'errors' => [
                'login_failed' => ucfirst(REG::loginType()) . ' or password incorrect.',
            ],
        ]));
    }

    protected function responceSessionExpiredConfirmation($data = [])
    {
        $confirmation = [
            'type' => 'error',
            'title' => 'Session expired',
            'message' => 'Your session is expired. Start registration process again.',
            'buttons' => [
                ['title' => 'OK', 'url' => route('auth.register')],
            ],
        ];

        $confirmation = array_merge($confirmation, $data);

        return response()->json(collect([
            'success' => false,
            'confirmation' => $confirmation,
        ]));
    }

    protected function responceWaitForCodeResendTimer($timer)
    {
        $confirmation = [
            'type' => 'warning',
            'title' => 'To often!',
            'message' => 'You can\'t resend code too often. Wait please while timer will be finished.',
            'buttons' => [
                ['title' => 'Resend code', 'style' => 'primary', 'callback' => 'confirmAccount', 'timer' => $timer],
                ['title' => 'Cancel', 'type' => 'cancel'],
            ],
        ];

        return response()->json(collect([
            'success' => false,
            'confirmation' => $confirmation,
        ]));
    }

    protected function responceCodeSent($method)
    {
        $confirmation = [
            'type' => 'success',
            'title' => 'Code sent',
            'message' => REG::loginType() == 'email' ? 
                'Code and confirmation link has been sent to your email. Link and code valid for '.REG::codeExpireIn().' minutes.' 
                : 
                'Code and confirmation link has been sent to your phone number. Link and code valid for '.REG::codeExpireIn().' minutes.',
            'buttons' => [
                ['title' => 'OK', 'type' => 'cancel'],
            ],
        ];

        return response()->json(collect([
            'success' => true,
            'url' => route('auth.verify', ['method' => $method]),
            'timer' => REG::getResendCodeTimer(),
            'confirmation' => $confirmation,
        ]));
    }

    protected function accountVerifedAndRedirect($data)   
    {
        $confirmation = [
            'type' => 'success',
            'title' => 'Account successfully verified',
            'message' => $data['message'],
            'buttons' => [
                ['title' => 'OK', 'url' => !empty($data['url']) ? $data['url'] : REG::getSuccessAuthUrl()],
            ],
        ];

        return response()->json(collect([
            'success' => true,
            'confirmation' => $confirmation,
        ]));
    }

    protected function accountNotVerifedAndRedirect($data)   
    {
        $confirmation = [
            'type' => 'success',
            'title' => 'Account not verified!',
            'message' => $data['message'],
            'buttons' => [
                ['title' => 'OK', 'url' => !empty($data['url']) ? $data['url'] : REG::getSuccessAuthUrl()],
            ],
        ];

        return response()->json(collect([
            'success' => true,
            'confirmation' => $confirmation,
        ]));
    }

    protected function passwordSetResponce($method, $active = false)   
    {
        $url = REG::getSuccessAuthUrl();
        if($method == 'recovery'){
            $message = 'Your password has been updated. Click OK button to automatically login.';
        } else {
            if($active){
                $message = 'Your account successfully created and activated. Click OK button to automatically login.';
            } else {
                $message = 'Your account successfully created. Your role required manually activation. Click OK button to redirect to the activation status page.';
                $url = route('auth.waiting_for_activate');
            }
        }

        $confirmation = [
            'type' => 'success',
            'title' => 'Done!',
            'message' => $message,
            'buttons' => [
                ['title' => 'OK', 'url' => $url],
            ],
        ];

        return response()->json(collect([
            'success' => true,
            'confirmation' => $confirmation,
        ]));
    }

}

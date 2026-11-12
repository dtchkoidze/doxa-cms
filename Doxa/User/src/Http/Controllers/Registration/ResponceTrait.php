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

    protected function responceUserAlreadyRegistred()
    {
        return response()->json(collect([
            'success' => false,
            'errors' => [
                'register_failed' => vocab('user_already_registered'),
            ],
        ]));
    }

    protected function responceLoginFailed()
    {
        return response()->json(collect([
            'success' => false,
            'errors' => [
                'login_failed' => vocab('user_login_incorrect'),
            ],
        ]));
    }

    protected function responceLoginLocked(int $retryAfter)
    {
        return response()->json(collect([
            'success' => false,
            'retry_after' => $retryAfter,
            'errors' => [
                'login_failed' => REG::loginLockoutMessage($retryAfter),
            ],
        ]));
    }

    protected function responceVerificationLocked(int $retryAfter)
    {
        return response()->json(collect([
            'success' => false,
            'retry_after' => $retryAfter,
            'error' => REG::verificationLockoutMessage($retryAfter),
        ]));
    }

    protected function responceSessionExpiredConfirmation($data = [])
    {
        $confirmation = [
            'type' => 'error',
            'title' => vocab('user_session_expired'),
            'message' => vocab('user_session_expired_register_text'),
            'buttons' => [
                ['title' => vocab('ok'), 'url' => route('auth.register')],
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
            'title' => vocab('user_too_often'),
            'message' => vocab('user_resend_too_often_text'),
            'buttons' => [
                ['title' => vocab('resend_code'), 'style' => 'primary', 'callback' => 'confirmAccount', 'timer' => $timer],
                ['title' => vocab('cancel'), 'type' => 'cancel'],
            ],
        ];

        return response()->json(collect([
            'success' => false,
            'confirmation' => $confirmation,
        ]));
    }

    protected function responceCodeSent($method)
    {
        // Восстановление: текст не раскрывает, зарегистрирован ли логин
        $variables = ['minutes' => REG::codeExpireIn()];
        if ($method === 'recovery') {
            $message = vocab('user_recovery_code_resent_text', $variables);
        } else {
            $message = REG::loginType() == 'email' ?
                vocab('user_code_sent_to_email_text', $variables)
                :
                vocab('user_code_sent_to_phone_text', $variables);
        }

        $confirmation = [
            'type' => 'success',
            'title' => vocab('user_code_sent'),
            'message' => $message,
            'buttons' => [
                ['title' => vocab('ok'), 'type' => 'cancel'],
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
            'title' => vocab('user_account_verified'),
            'message' => $data['message'],
            'buttons' => [
                ['title' => vocab('ok'), 'url' => !empty($data['url']) ? $data['url'] : REG::getSuccessAuthUrl()],
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
            'title' => vocab('user_account_not_verified'),
            'message' => $data['message'],
            'buttons' => [
                ['title' => vocab('ok'), 'url' => !empty($data['url']) ? $data['url'] : REG::getSuccessAuthUrl()],
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
            $message = vocab('user_password_updated_text');
        } else {
            if($active){
                $message = vocab('user_account_created_active_text');
            } else {
                $message = vocab('user_account_created_inactive_text');
                $url = route('auth.waiting_for_activate');
            }
        }

        $confirmation = [
            'type' => 'success',
            'title' => vocab('done'),
            'message' => $message,
            'buttons' => [
                ['title' => vocab('ok'), 'url' => $url],
            ],
        ];

        return response()->json(collect([
            'success' => true,
            'confirmation' => $confirmation,
        ]));
    }

}

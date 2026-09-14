@component('mail::message')
{!! textblock('email.add_email_verification', [
    'email' => $email,
    'code' => $code,
    'code_expire_in' => $code_expire_in,
]) !!}
@endcomponent

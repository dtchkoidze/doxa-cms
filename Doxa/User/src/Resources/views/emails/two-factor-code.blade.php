@component('mail::message')
{!! textblock('email.two_factor_code', [
    'email' => $email,
    'code' => $code,
    'code_expire_in' => $code_expire_in,
]) !!}
@endcomponent

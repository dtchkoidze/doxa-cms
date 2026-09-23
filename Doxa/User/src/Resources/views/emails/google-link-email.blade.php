@component('mail::message')
{!! textblock('email.google_link', [
    'email' => $email,
    'code' => $code,
    'code_expire_in' => $code_expire_in,
]) !!}
@endcomponent

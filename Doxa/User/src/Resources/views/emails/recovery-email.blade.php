@component('mail::message')
{!! textblock('email.recovery', [
    'email' => $email,
    'secret' => $secret,
]) !!}
@endcomponent

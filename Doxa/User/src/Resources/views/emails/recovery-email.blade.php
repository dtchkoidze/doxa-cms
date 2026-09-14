@component('mail::message')
{!! textblock('email.recovery', [
    'email' => $email,
    'secret' => $secret,
    'verification_link' => $verification_link,
]) !!}
@endcomponent

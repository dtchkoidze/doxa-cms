@component('mail::message')
{!! textblock('email.verification', [
    'app_name' => projectTitle(),
    'email' => $email,
    'secret' => $secret,
    'verification_link' => $verification_link,
]) !!}
@endcomponent

@component('mail::message')
{!! textblock('email.account_deletion', [
    'email' => $email,
    'secret' => $secret,
    'verification_link' => $verification_link,
]) !!}
@endcomponent

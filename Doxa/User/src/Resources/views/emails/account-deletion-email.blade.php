@component('mail::message')
{!! textblock('email.account_deletion', [
    'email' => $email,
    'secret' => $secret,
]) !!}
@endcomponent

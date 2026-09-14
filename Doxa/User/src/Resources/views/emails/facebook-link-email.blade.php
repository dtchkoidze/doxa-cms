@component('mail::message')
{!! textblock('email.facebook_link', [
    'email' => $email,
    'link' => $link,
    'expires_minutes' => $expires_minutes,
]) !!}
@endcomponent

@component('mail::message')
{!! textblock('email.google_link', [
    'email' => $email,
    'link' => $link,
    'expires_minutes' => $expires_minutes,
]) !!}
@endcomponent

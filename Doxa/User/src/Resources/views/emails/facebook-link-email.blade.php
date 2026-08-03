@component('mail::message')
# Confirm Facebook account link

You asked to link your Facebook account to **{{ $email }}**.

Click the button below to confirm. After that you will be able to sign in with either your password or Facebook.

@component('mail::button', ['url' => $link])
Confirm Facebook link
@endcomponent

This link is valid for {{ $expires_minutes }} minutes.

If you did not request this, no further action is required, ignore this email — your account will stay unchanged.
@endcomponent

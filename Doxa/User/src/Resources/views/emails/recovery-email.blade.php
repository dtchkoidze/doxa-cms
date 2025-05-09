@component('mail::message')
# Recovering Password!

You are receiving this email because we received a password recovery request for your account.

Please verify your email address by clicking the button below or copying and pasting the secret code into the verification field.

Your Email: {{ $email }}

**Secret:** {{ $secret }}

@component('mail::button', ['url' => $verification_link])
Verify Email
@endcomponent


If you did not request password recovery, no further action is required, ignore this email.

@endcomponent

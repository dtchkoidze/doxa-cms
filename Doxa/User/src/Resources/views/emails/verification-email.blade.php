@component('mail::message')
# Welcome!

We are excited to have you on {{ config('app.name') }}.

Please verify your email address by clicking the button below or copying and pasting the secret code into the verification field.

Your Email: {{ $email }}

**Secret:** {{ $secret }}

@component('mail::button', ['url' => $verification_link])
Verify Email
@endcomponent



If you did not create an account, no further action is required, ignore this email.

@endcomponent

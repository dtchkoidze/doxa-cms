@component('mail::message')
# Account Deletion!

You are receiving this email because we received an account deletion request.

Please note that once your account is deleted, it cannot be restored.

Please verify your email address by clicking the button below or copying and pasting the secret code into the
verification field.

Your Email: {{ $email }}

**Secret:** {{ $secret }}

@component('mail::button', ['url' => $verification_link])
    Verify Email and Delete Account
@endcomponent


If you did not request account deleteion, no further action is required, ignore this email.
@endcomponent

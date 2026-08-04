@component('mail::message')
# Confirm your email

Your verification code is:

**{{ $code }}**

This code expires in {{ $code_expire_in }} minutes.

If you did not request this, you can ignore this email.
@endcomponent

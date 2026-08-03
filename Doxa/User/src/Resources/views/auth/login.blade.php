@extends($wrapper)
@section('content')
    <Login
        :google-auth-enabled="{{ json_encode((bool) config('services.google.auth_enabled')) }}"
        :facebook-auth-enabled="{{ json_encode((bool) config('services.facebook.auth_enabled')) }}"
    />
@endsection

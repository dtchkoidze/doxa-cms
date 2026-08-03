@extends($wrapper)
@section('content')
    <Register
        :roles="{{ json_encode($roles) }}"
        :google-auth-enabled="{{ json_encode((bool) config('services.google.auth_enabled')) }}"
        :facebook-auth-enabled="{{ json_encode((bool) config('services.facebook.auth_enabled')) }}"
    />
@endsection

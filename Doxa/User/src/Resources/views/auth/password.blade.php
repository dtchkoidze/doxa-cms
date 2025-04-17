@extends($wrapper)
@section('content')
    <Password 
        {{-- :login="{{ json_encode($login) }}"
        :login_type="{{ json_encode($login_type) }}" --}}
        :method="{{ json_encode($method) }}"
    />
@endsection
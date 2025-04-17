@extends($wrapper)
@section('content')
    <wrong-verification-link 
        :login="{{ json_encode($login) }}"
        :login_type="{{ json_encode($login_type) }}"
        :method="{{ json_encode($method) }}"
        :timer="{{ json_encode($timer) }}"
        :description="{{ json_encode($description) }}"
        :header="{{ json_encode($header) }}"
    />
@endsection
@extends($wrapper)
@section('content')
    <Verify 
        :login="{{ json_encode($login) }}"
        :login_type="{{ json_encode($login_type) }}"
        :method="{{ json_encode($method) }}"
        :timer="{{ json_encode($timer) }}"
        :code_expire_in="{{ json_encode($code_expire_in) }}"
    />
@endsection
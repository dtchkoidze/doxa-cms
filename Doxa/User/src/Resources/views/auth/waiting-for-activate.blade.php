@extends($wrapper)
@section('content')
    <waiting-for-activate 
        :login="{{ json_encode($login) }}"
        :login_type="{{ json_encode($login_type) }}"
    />
@endsection
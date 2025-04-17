@extends($wrapper)
@section('content')
    <session-expired :code_expire_in="{{ json_encode($code_expire_in) }}"/>
@endsection
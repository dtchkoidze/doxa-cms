@extends($wrapper)
@section('content')
    <Register :roles="{{ json_encode($roles) }}"/>
@endsection

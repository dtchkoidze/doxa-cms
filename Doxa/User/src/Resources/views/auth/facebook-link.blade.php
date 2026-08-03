@extends($wrapper)
@section('content')
    <facebook-link :email="{{ json_encode($email) }}" />
@endsection

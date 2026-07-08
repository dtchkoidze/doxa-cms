@extends($wrapper)
@section('content')
    <google-link :email="{{ json_encode($email) }}" />
@endsection

@extends($wrapper)
@section('content')
    <two-factor :challenge="{{ json_encode($challenge) }}" />
@endsection

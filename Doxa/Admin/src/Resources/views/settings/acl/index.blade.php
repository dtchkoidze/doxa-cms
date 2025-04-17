@extends('admin::layouts.base')
@section('content')
    <acl :tree="{{ json_encode($mergedAcl) }}" :role="{{ json_encode($role) }}"></acl>
@endsection

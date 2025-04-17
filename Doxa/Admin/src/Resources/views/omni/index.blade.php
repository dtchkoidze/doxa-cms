@extends('admin::layouts.base')
@section('content')
    <omni 
        id="{{ isset($id) ? $id : '' }}" 
        module="{{ $module }}"
        mode="{{ $mode }}"
        method="{{ $method }}"
        copied="{{ isset($copied) ? $copied : 0 }}"
    />
@endsection
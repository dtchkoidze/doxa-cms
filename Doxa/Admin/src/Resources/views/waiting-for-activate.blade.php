@extends('user::layouts.base')
@section('content')
    <div id="app">
        <div class="w-full max-w-sm px-4 py-8 mx-auto">
        
            <h1 class="mb-2 text-3xl font-bold text-gray-800 dark:text-gray-100">Activation required</h1>

            <div class="mb-2 sm">
                Your email is verifed. At the moment, your admin account is not active. We must review your account and activate it.
            </div>

            <div class="mb-6 sm">
                Account activation may take time. Please wait. If you you're waiting too long, contact website administrator.
            </div>

            <div class="text-sm"> 
                <a class="btn-primary" href="{{ route($src.'.logout') }}">Logout</a>
            </div>

        </div>
    </div>
@endsection
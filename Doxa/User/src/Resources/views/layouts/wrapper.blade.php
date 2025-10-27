@extends('user::layouts.base')
@section('wrapper')
    <main class="bg-white dark:bg-gray-900" id="app">
        <div class="relative flex">

            <!-- Content -->
            <div class="w-full md:w-1/2">

                <div class="min-h-[100dvh] h-full flex flex-col after:flex-1">
                    <!-- Header -->
                    <div class="flex-1">
                        <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                            <!-- Logo -->
                            <img src="/storage/logo.png">
                        </div>
                    </div>

                    @yield('content')

                </div>

            </div>

            <!-- Image -->
            <div class="absolute top-0 bottom-0 right-0 hidden md:block md:w-1/2" aria-hidden="true">
                <img class="object-cover object-center w-full h-full" src="/storage/images/auth-image.jpg" width="760" height="1024" alt="Authentication image" />
            </div>

        </div>

    </main>
    <div class="hidden w-24"></div>
@endsection

@extends($wrapper)
@section('content')
    <div class="w-full max-w-sm px-4 mx-auto">
        <h1 class="mb-6 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $error['title'] }}</h1>
        <div class="flex items-center justify-between mt-6 mb-6">
            <div class="mr-1">
                {{ $error['message'] }}                 
            </div>
        </div>

        <!------------ FOOTER -------------->
        <div class="flex flex-col mt-12">
            <div class="flex justify-between py-3 text-sm border-t border-b border-gray-100 dark:border-gray-700/60">
                Already registered? 
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/login">
                    Sign In
                </a>
            </div>
            <div class="flex justify-between py-3 text-sm border-b border-gray-100 dark:border-gray-700/60">
                Don't have an account? 
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/register">
                    Sign Up
                </a>
            </div>
            <div class="flex justify-between py-3 text-sm border-b border-gray-100 dark:border-gray-700/60">
                Can't access your account? 
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/recovery">
                    Recover Access
                </a>
            </div>
        </div> 
    </div>
@endsection
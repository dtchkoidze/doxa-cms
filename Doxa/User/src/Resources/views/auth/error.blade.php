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
                {{ vocab('user_already_registered_question') }}
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/login">
                    {{ vocab('log_in') }}
                </a>
            </div>
            <div class="flex justify-between py-3 text-sm border-b border-gray-100 dark:border-gray-700/60">
                {{ vocab('user_no_account') }}
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/register">
                    {{ vocab('register') }}
                </a>
            </div>
            <div class="flex justify-between py-3 text-sm border-b border-gray-100 dark:border-gray-700/60">
                {{ vocab('user_cant_access_account') }}
                <a
                    class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400"
                    href="/auth/recovery">
                    {{ vocab('user_recover_access') }}
                </a>
            </div>
        </div> 
    </div>
@endsection
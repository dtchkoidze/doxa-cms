@props(['route_prefix' => 'admin'])

<div class="pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
    <div class="flex items-center gap-4 text-sm">
        <a class="text-gray-800 bg-white border-gray-200 btn dark:bg-gray-800 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 dark:text-gray-300" href="{{ route($route_prefix.'.register') }}">Sign Up</a>
        <a class="text-gray-100 bg-gray-900 btn hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white" href="{{ route($route_prefix.'.login') }}">Sign In</a>
    </div>
</div>
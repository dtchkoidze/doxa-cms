@extends('admin::layouts.base')
@section('content')
 <div class=" max-w-md p-4 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-md">
    <ul>
        @foreach (json_decode(auth()->user()) as $key => $val)
            <li class="flex justify-between items-center py-2 px-4 {{ $loop->even ? 'bg-gray-100 dark:bg-gray-700' : 'bg-gray-50 dark:bg-gray-800' }}">
                <span class="font-semibold text-gray-600 dark:text-gray-200">{{ ucfirst($key) }}</span>
                <span class="text-gray-800 dark:text-gray-100">{{ $val }}</span>
            </li>
        @endforeach
        <li class="flex justify-between items-center py-2 px-4 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-semibold">
            <span>User Role</span>
            <span>{{ session('user_role') }}</span>
        </li>
    </ul>
</div>

@endsection

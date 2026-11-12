@extends($wrapper)
@section('content')
    <div class="w-full max-w-sm px-4 mx-auto">
        <h1 class="mb-6 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ vocab('mcp_consent_error_title') }}</h1>
        <p class="mb-2">{{ vocab('mcp_consent_error_text') }}</p>
        <p class="text-sm">{{ $error }}: {{ $errorDescription }}</p>
    </div>
@endsection

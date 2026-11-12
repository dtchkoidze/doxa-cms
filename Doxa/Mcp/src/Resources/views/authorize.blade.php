@extends($wrapper)
@section('content')
    <div class="w-full max-w-sm px-4 mx-auto">
        <h1 class="mb-6 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ vocab('mcp_consent_title') }}</h1>

        <p class="mb-4">
            <b>{{ $clientName }}</b>
            {{ vocab('mcp_consent_requests_access') }}
            <b>{{ projectTitle() }}</b>
        </p>
        <p class="mb-6">{{ vocab('mcp_consent_scope') }}</p>

        <div class="py-3 mb-6 text-sm border-t border-b border-gray-100 dark:border-gray-700/60">
            {{ vocab('mcp_consent_signed_in_as') }}
            <b>{{ $userName }}</b>
            <a class="ml-1 font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400" href="{{ $switchAccountUrl }}">
                {{ vocab('mcp_consent_switch_account') }}
            </a>
        </div>

        <form method="POST" action="{{ url('/oauth/authorize') }}" class="flex gap-3">
            @csrf
            @foreach ($params as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <button type="submit" name="decision" value="deny" class="w-1/2 btn btn-secondary">
                {{ vocab('mcp_consent_deny') }}
            </button>
            <button type="submit" name="decision" value="allow" class="w-1/2 btn btn-primary">
                {{ vocab('mcp_consent_allow') }}
            </button>
        </form>
    </div>
@endsection

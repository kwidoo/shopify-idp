<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('API Tokens') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- API Token Manager -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        Create API Token
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        API tokens allow third-party services to authenticate with our application on your behalf.
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Token Creation Form -->
                <form method="POST" action="{{ route('api-tokens.store') }}">
                    @csrf

                    <!-- Token Name -->
                    <div class="mb-4">
                        <x-label for="name" :value="__('Token Name')" />
                        <x-input id="name" class="block mt-1 w-full" type="text" name="name"
                            :value="old('name')" required autofocus placeholder="My App Token" />
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Token Permissions -->
                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">
                            {{ __('Permissions') }}
                        </label>

                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($availableScopes as $scope => $description)
                                <div class="flex items-center">
                                    <input type="checkbox" id="scope_{{ $scope }}" name="scopes[]"
                                        value="{{ $scope }}"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <label for="scope_{{ $scope }}" class="ml-2 text-sm text-gray-600">
                                        {{ $description }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @error('scopes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        @error('scopes.*')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <x-button type="submit">
                            {{ __('Create API Token') }}
                        </x-button>
                    </div>
                </form>
            </div>

            <!-- Display Newly Created Token -->
            @if (session('token') && session('refresh_token'))
                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <div class="font-semibold text-sm text-gray-600 mb-2">
                            {{ __('Please copy your new API token. For your security, it won\'t be shown again.') }}
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">
                                {{ __('Access Token') }}
                            </label>
                            <textarea class="block w-full mt-1 px-3 py-2 text-gray-700 border rounded-md focus:outline-none" rows="1" readonly
                                onclick="this.select();">{{ session('token') }}</textarea>
                        </div>

                        <div class="mt-4">
                            <label class="block font-medium text-sm text-gray-700 mb-1">
                                {{ __('Refresh Token') }}
                            </label>
                            <textarea class="block w-full mt-1 px-3 py-2 text-gray-700 border rounded-md focus:outline-none" rows="1" readonly
                                onclick="this.select();">{{ session('refresh_token') }}</textarea>
                        </div>

                        <div class="mt-4 text-sm text-gray-600">
                            <p>{{ __('Store these tokens securely. The refresh token can be used to generate new access tokens when they expire.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Existing Tokens List -->
            @if (count($tokens) > 0)
                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('Your API Tokens') }}
                    </h3>

                    <div class="space-y-6">
                        @foreach ($tokens as $token)
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $token['name'] }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        Created {{ \Carbon\Carbon::parse($token['created_at'])->diffForHumans() }}
                                        • Expires {{ \Carbon\Carbon::parse($token['expires_at'])->diffForHumans() }}
                                    </div>

                                    @if (count($token['scopes']) > 0)
                                        <div class="mt-1">
                                            @foreach ($token['scopes'] as $scope)
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                                                    {{ $scope }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center">
                                    <form method="POST" action="{{ route('api-tokens.destroy', $token['id']) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="text-sm text-red-500 cursor-pointer"
                                            onclick="return confirm('Are you sure you want to revoke this token?');">
                                            {{ __('Revoke') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- API Documentation -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    {{ __('API Usage Instructions') }}
                </h3>

                <div class="mt-4 text-sm text-gray-600 space-y-4">
                    <div>
                        <h4 class="font-medium">Authentication</h4>
                        <p>Use your access token in API requests by including it in the Authorization header:</p>
                        <pre class="bg-gray-100 rounded p-2 mt-1 overflow-x-auto">Authorization: Bearer YOUR_ACCESS_TOKEN</pre>
                    </div>

                    <div>
                        <h4 class="font-medium">Refreshing Tokens</h4>
                        <p>When your access token expires, use your refresh token to get a new one:</p>
                        <pre class="bg-gray-100 rounded p-2 mt-1 overflow-x-auto">POST /api/tokens/refresh
Content-Type: application/json

{
    "refresh_token": "YOUR_REFRESH_TOKEN"
}</pre>
                    </div>

                    <div>
                        <h4 class="font-medium">Revoking Tokens</h4>
                        <p>To revoke a refresh token:</p>
                        <pre class="bg-gray-100 rounded p-2 mt-1 overflow-x-auto">POST /api/tokens/revoke
Content-Type: application/json

{
    "refresh_token": "YOUR_REFRESH_TOKEN"
}</pre>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

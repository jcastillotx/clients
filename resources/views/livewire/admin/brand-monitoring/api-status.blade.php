<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">API Connection Status</h2>
            <p class="mt-1 text-sm text-gray-600">Test and monitor your brand monitoring API integrations</p>
        </div>
        <a href="{{ route('admin.brand-monitoring.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            ← Back to Dashboard
        </a>
    </div>

    {{-- Setup Instructions --}}
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Need help setting up?</strong> Check the
                    <a href="{{ asset('docs/brand-monitoring-setup.md') }}" class="font-medium underline" target="_blank">setup guide</a>
                    for step-by-step instructions to get free API keys.
                </p>
            </div>
        </div>
    </div>

    {{-- News APIs --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">News Monitoring APIs</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($apis as $key => $api)
                @if($api['category'] === 'News')
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-sm font-medium text-gray-900">{{ $api['name'] }}</h4>
                                @if($api['enabled'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Disabled</span>
                                @endif
                                @if($api['configured'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Configured</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Not Configured</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Free Tier: {{ $api['limit'] }}</p>

                            @if(isset($testResults[$key]))
                            <div class="mt-2">
                                @if($testResults[$key]['status'] === 'success')
                                <p class="text-sm text-green-600">✓ {{ $testResults[$key]['message'] }}</p>
                                @else
                                <p class="text-sm text-red-600">✗ {{ $testResults[$key]['message'] }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                        @if(method_exists($this, 'test' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Api'))
                        <button
                            wire:click="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            wire:loading.attr="disabled"
                            wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            class="ml-4 inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Test Connection</span>
                            <span wire:loading wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Testing...</span>
                        </button>
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Review APIs --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">Review Monitoring APIs</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($apis as $key => $api)
                @if($api['category'] === 'Reviews')
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-sm font-medium text-gray-900">{{ $api['name'] }}</h4>
                                @if($api['enabled'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Disabled</span>
                                @endif
                                @if($api['configured'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Configured</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Not Configured</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Free Tier: {{ $api['limit'] }}</p>

                            @if(isset($testResults[$key]))
                            <div class="mt-2">
                                @if($testResults[$key]['status'] === 'success')
                                <p class="text-sm text-green-600">✓ {{ $testResults[$key]['message'] }}</p>
                                @else
                                <p class="text-sm text-red-600">✗ {{ $testResults[$key]['message'] }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                        @if(method_exists($this, 'test' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Api'))
                        <button
                            wire:click="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            wire:loading.attr="disabled"
                            wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            class="ml-4 inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Test Connection</span>
                            <span wire:loading wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Testing...</span>
                        </button>
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Social Media APIs --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">Social Media Monitoring APIs</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($apis as $key => $api)
                @if($api['category'] === 'Social')
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-sm font-medium text-gray-900">{{ $api['name'] }}</h4>
                                @if($api['enabled'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Disabled</span>
                                @endif
                                @if($api['configured'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Configured</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Not Configured</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Free Tier: {{ $api['limit'] }}</p>

                            @if(isset($testResults[$key]))
                            <div class="mt-2">
                                @if($testResults[$key]['status'] === 'success')
                                <p class="text-sm text-green-600">✓ {{ $testResults[$key]['message'] }}</p>
                                @else
                                <p class="text-sm text-red-600">✗ {{ $testResults[$key]['message'] }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                        @if(method_exists($this, 'test' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Api'))
                        <button
                            wire:click="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            wire:loading.attr="disabled"
                            wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            class="ml-4 inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Test Connection</span>
                            <span wire:loading wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Testing...</span>
                        </button>
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Web Mention APIs --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">Web Mention Monitoring APIs</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($apis as $key => $api)
                @if($api['category'] === 'Web')
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-sm font-medium text-gray-900">{{ $api['name'] }}</h4>
                                @if($api['enabled'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Disabled</span>
                                @endif
                                @if($api['configured'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Configured</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Not Configured</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Free Tier: {{ $api['limit'] }}</p>

                            @if(isset($testResults[$key]))
                            <div class="mt-2">
                                @if($testResults[$key]['status'] === 'success')
                                <p class="text-sm text-green-600">✓ {{ $testResults[$key]['message'] }}</p>
                                @else
                                <p class="text-sm text-red-600">✗ {{ $testResults[$key]['message'] }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                        @if(method_exists($this, 'test' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Api'))
                        <button
                            wire:click="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            wire:loading.attr="disabled"
                            wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api"
                            class="ml-4 inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Test Connection</span>
                            <span wire:loading wire:target="test{{ str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) }}Api">Testing...</span>
                        </button>
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Cost Summary --}}
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Cost Summary</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Total Monthly Cost (all free tiers):</span>
                <span class="font-semibold text-green-600">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Estimated AI Sentiment Analysis:</span>
                <span class="font-semibold text-gray-900">~$5-15/month</span>
            </div>
            <div class="flex justify-between border-t pt-2">
                <span class="text-gray-900 font-medium">Total Estimated Cost:</span>
                <span class="font-bold text-gray-900">$5-15/month</span>
            </div>
            <p class="text-xs text-gray-500 pt-2">Replaces expensive platforms like Brandwatch ($800-2000/month) - saving 95-99%</p>
        </div>
    </div>
</div>

<div class="space-y-6">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Brand Monitoring API Configuration</h2>
        <p class="text-sm text-slate-500 mt-1">Configure API keys for brand monitoring services. Most have generous free tiers.</p>
    </div>

    <form wire:submit.prevent="saveBrandMonitoringSettings" class="space-y-8">
        <!-- News Monitoring Section -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">News Monitoring</h3>
            
            <!-- NewsAPI -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd" />
                                <path d="M15 7h1a2 2 0 012 2v5.5a1.5 1.5 0 01-3 0V7z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">NewsAPI</h4>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: 100 req/day</span>
                        </div>
                    </div>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'newsapi'])
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                        <input type="password" wire:model="brandMonitoring.newsapi_api_key" placeholder="NewsAPI Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" id="newsapi_enabled" wire:model="brandMonitoring.newsapi_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                            <span class="text-sm text-slate-700">Enabled</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Monitoring Section -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Social Media Monitoring</h3>
            <div class="space-y-4">
                <!-- YouTube -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 7l5 3-5 3V7z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">YouTube Data API</h4>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: 10k units/day</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'youtube'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="brandMonitoring.youtube_api_key" placeholder="YouTube API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="youtube_enabled" wire:model="brandMonitoring.youtube_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Reddit -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM7 9a1 1 0 112 0 1 1 0 01-2 0zm5 0a1 1 0 112 0 1 1 0 01-2 0zm-4.5 3.5a3.5 3.5 0 017 0h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Reddit API</h4>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: 60 req/min</span>
                        </div>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client ID</label>
                            <input type="text" wire:model="brandMonitoring.reddit_client_id" placeholder="Reddit Client ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client Secret</label>
                            <input type="password" wire:model="brandMonitoring.reddit_client_secret" placeholder="Reddit Client Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="reddit_enabled" wire:model="brandMonitoring.reddit_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Facebook Mentions -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8.5 7.5A1.5 1.5 0 0110 6h1a1.5 1.5 0 011.5 1.5V8h-1v-.5a.5.5 0 00-.5-.5h-1a.5.5 0 00-.5.5V9a.5.5 0 00.5.5H11a1.5 1.5 0 011.5 1.5V12a1.5 1.5 0 01-1.5 1.5H9a1.5 1.5 0 01-1.5-1.5V11h1v1a.5.5 0 00.5.5h2a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5H9a1.5 1.5 0 01-1.5-1.5V7.5z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-900">Facebook Graph API (Mentions)</h4>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Page Access Token</label>
                            <input type="password" wire:model="brandMonitoring.facebook_access_token" placeholder="Facebook Page Access Token" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <p class="mt-1.5 text-xs text-slate-500">Long-lived page access token for monitoring mentions</p>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="facebook_enabled" wire:model="brandMonitoring.facebook_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Monitoring Section -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Review Monitoring</h3>
            <div class="space-y-4">
                <!-- Yelp -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Yelp Fusion API</h4>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: 5k req/day</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'yelp'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="brandMonitoring.yelp_api_key" placeholder="Yelp API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="yelp_enabled" wire:model="brandMonitoring.yelp_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Google Places -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Google Places API</h4>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: $200 credit/mo</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'google_places'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="brandMonitoring.google_places_api_key" placeholder="Google Places API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="google_places_enabled" wire:model="brandMonitoring.google_places_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Trustpilot -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-900">Trustpilot</h4>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="brandMonitoring.trustpilot_api_key" placeholder="Trustpilot API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Secret</label>
                            <input type="password" wire:model="brandMonitoring.trustpilot_api_secret" placeholder="Trustpilot API Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="trustpilot_enabled" wire:model="brandMonitoring.trustpilot_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- G2 and Capterra in a grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- G2 -->
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-semibold text-slate-900">G2 Crowd</h4>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                                <input type="password" wire:model="brandMonitoring.g2_api_key" placeholder="G2 API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="g2_enabled" wire:model="brandMonitoring.g2_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>

                    <!-- Capterra -->
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-semibold text-slate-900">Capterra</h4>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                                <input type="password" wire:model="brandMonitoring.capterra_api_key" placeholder="Capterra API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="capterra_enabled" wire:model="brandMonitoring.capterra_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Web Search Section -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Web Search</h3>
            <div class="space-y-4">
                <!-- Google Custom Search -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Google Custom Search</h4>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: 100 queries/day</span>
                        </div>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="brandMonitoring.google_search_api_key" placeholder="Google API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Search Engine ID</label>
                            <input type="text" wire:model="brandMonitoring.google_search_engine_id" placeholder="Custom Search Engine ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="google_search_enabled" wire:model="brandMonitoring.google_search_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Bing Search -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 3h6v6H3V3zm8 0h6v6h-6V3zM3 11h6v6H3v-6zm8 0h6v6h-6v-6z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Bing Web Search</h4>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: 1k queries/mo</span>
                        </div>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="brandMonitoring.bing_search_api_key" placeholder="Bing Search API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="bing_search_enabled" wire:model="brandMonitoring.bing_search_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveBrandMonitoringSettings">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293z" />
                    </svg>
                    Save Brand Monitoring Settings
                </span>
                <span wire:loading wire:target="saveBrandMonitoringSettings" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>
</div>

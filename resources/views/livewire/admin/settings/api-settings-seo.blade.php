<div class="space-y-6">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">SEO Integration API Configuration</h2>
        <p class="text-sm text-slate-500 mt-1">Configure API keys for SEO tools. We prioritize free/organic capabilities before commercial ones.</p>
        <div class="mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-emerald-800">
                    <strong>Priority:</strong> Built-in crawler & AI analysis (free) → Free APIs → Low-cost APIs → Commercial APIs
                </div>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="saveSeoSettings" class="space-y-8">
        <!-- Built-in Organic Capabilities (Always Free) -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">FREE</span>
                Built-in Organic Capabilities
            </h3>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-emerald-100">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Website Crawler</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Technical SEO audits, meta analysis, heading structure</p>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 mt-1">Always Active</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-emerald-100">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">AI Content Analysis</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Keyword suggestions, content optimization, LSI keywords</p>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 mt-1">Uses Your AI Provider</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-emerald-100">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Schema Validator</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Structured data detection & validation</p>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 mt-1">Always Active</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-emerald-100">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Readability Analysis</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Flesch reading ease, content quality metrics</p>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 mt-1">Always Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Free APIs Section -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">FREE</span>
                Free APIs (Generous Free Tiers)
            </h3>
            <div class="space-y-4">
                <!-- Google Search Console -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Google Search Console</h4>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: Your Own Data</span>
                        </div>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client ID</label>
                            <input type="text" wire:model="seo.google_search_console_client_id" placeholder="Google Client ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client Secret</label>
                            <input type="password" wire:model="seo.google_search_console_client_secret" placeholder="Google Client Secret" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Refresh Token</label>
                            <input type="password" wire:model="seo.google_search_console_refresh_token" placeholder="OAuth Refresh Token" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.google_search_console_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Google PageSpeed Insights -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Google PageSpeed Insights</h4>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: 25k queries/day</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'google_pagespeed'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key (Optional)</label>
                            <input type="password" wire:model="seo.google_pagespeed_api_key" placeholder="API Key (higher limits)" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <p class="mt-1.5 text-xs text-slate-500">Works without key but with lower rate limits</p>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.google_pagespeed_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Bing Webmaster -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 3h6v6H3V3zm8 0h6v6h-6V3zM3 11h6v6H3v-6zm8 0h6v6h-6v-6z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Bing Webmaster Tools</h4>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Free: Your Own Data</span>
                        </div>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="seo.bing_webmaster_api_key" placeholder="Bing Webmaster API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.bing_webmaster_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Freemium: Ubersuggest & Keywords Everywhere -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Ubersuggest</h4>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Freemium: 3/day</span>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                                <input type="password" wire:model="seo.ubersuggest_api_key" placeholder="Ubersuggest API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.ubersuggest_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Keywords Everywhere</h4>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Freemium</span>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                                <input type="password" wire:model="seo.keywords_everywhere_api_key" placeholder="Keywords Everywhere API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.keywords_everywhere_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low-Cost APIs Section -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">LOW-COST</span>
                Affordable APIs (Under $100/month)
            </h3>
            <div class="space-y-4">
                <!-- DataForSEO -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z"/>
                                    <path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z"/>
                                    <path d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">DataForSEO</h4>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">From $50/mo (pay-per-use)</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'dataforseo'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Login</label>
                            <input type="text" wire:model="seo.dataforseo_login" placeholder="DataForSEO Login" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Password</label>
                            <input type="password" wire:model="seo.dataforseo_password" placeholder="DataForSEO Password" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.dataforseo_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-5 pb-4">
                        <p class="text-xs text-slate-500">Features: SERP tracking, keyword data, backlinks, on-page SEO, content analysis</p>
                    </div>
                </div>

                <!-- SerpApi -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">SerpApi</h4>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">From $50/mo</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'serpapi'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="seo.serpapi_api_key" placeholder="SerpApi Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.serpapi_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Mangools, SpyFu, Majestic grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Mangools -->
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Mangools</h4>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">$29.90/mo</span>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                                <input type="password" wire:model="seo.mangools_api_key" placeholder="Mangools API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.mangools_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>

                    <!-- SpyFu -->
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">SpyFu</h4>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">$39/mo</span>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                                <input type="password" wire:model="seo.spyfu_api_key" placeholder="SpyFu API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.spyfu_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>

                    <!-- Majestic -->
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-pink-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Majestic</h4>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">$49.99/mo</span>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                                <input type="password" wire:model="seo.majestic_api_key" placeholder="Majestic API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.majestic_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commercial Enterprise APIs Section -->
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">COMMERCIAL</span>
                Enterprise APIs ($100+/month)
            </h3>
            <div class="mb-4 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-purple-800">
                        <strong>Note:</strong> These premium APIs provide the most comprehensive data. Only enable if free/low-cost options don't meet your needs.
                    </div>
                </div>
            </div>
            <div class="space-y-4">
                <!-- Moz -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <span class="text-xs font-bold text-blue-600">Moz</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Moz Pro</h4>
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">From $99/mo</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'moz'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Access ID</label>
                            <input type="text" wire:model="seo.moz_access_id" placeholder="Moz Access ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Secret Key</label>
                            <input type="password" wire:model="seo.moz_secret_key" placeholder="Moz Secret Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.moz_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-5 pb-4">
                        <p class="text-xs text-slate-500">Features: Domain Authority, Page Authority, Spam Score, Link Metrics, Keyword Explorer</p>
                    </div>
                </div>

                <!-- Ahrefs -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                                <span class="text-xs font-bold text-orange-600">Ah</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Ahrefs</h4>
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">From $99/mo (API: $399/mo)</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'ahrefs'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="seo.ahrefs_api_key" placeholder="Ahrefs API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.ahrefs_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-5 pb-4">
                        <p class="text-xs text-slate-500">Features: Domain Rating, URL Rating, Backlinks, Organic Keywords, Content Explorer, Site Audit</p>
                    </div>
                </div>

                <!-- SEMrush -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <span class="text-xs font-bold text-red-600">SE</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">SEMrush</h4>
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">From $129.95/mo</span>
                            </div>
                        </div>
                        @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'semrush'])
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">API Key</label>
                            <input type="password" wire:model="seo.semrush_api_key" placeholder="SEMrush API Key" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.semrush_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-5 pb-4">
                        <p class="text-xs text-slate-500">Features: Domain Analytics, Keyword Analytics, Backlinks, Position Tracking, Site Audit, Competitor Research</p>
                    </div>
                </div>

                <!-- Screaming Frog -->
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Screaming Frog SEO Spider</h4>
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">£199/year</span>
                        </div>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">License Key</label>
                            <input type="password" wire:model="seo.screaming_frog_license_key" placeholder="Screaming Frog License" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <p class="mt-1.5 text-xs text-slate-500">Local CLI tool for deep crawls</p>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="seo.screaming_frog_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveSeoSettings">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293z" />
                    </svg>
                    Save SEO Settings
                </span>
                <span wire:loading wire:target="saveSeoSettings" class="flex items-center gap-2">
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

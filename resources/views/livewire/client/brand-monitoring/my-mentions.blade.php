<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900">My Brand Mentions</h2>
        <p class="mt-1 text-sm text-gray-600">Track what people are saying about your brand across the web</p>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Mentions</dt>
                            <dd class="text-2xl font-bold text-gray-900">{{ number_format($totalMentions) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-400 to-green-500 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-white truncate">Positive Reviews</dt>
                            <dd class="text-2xl font-bold text-white">{{ $recentPositive }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-red-400 to-red-500 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-white truncate">Needs Attention</dt>
                            <dd class="text-2xl font-bold text-white">{{ $recentNegative }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($recentNegative > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>{{ $recentNegative }} negative mention(s)</strong> detected in the selected period. Review and respond promptly.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Platform</label>
                <select wire:model="selectedPlatform" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Platforms</option>
                    @foreach($platforms as $platform)
                    <option value="{{ $platform }}">{{ ucwords(str_replace('_', ' ', $platform)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Sentiment</label>
                <select wire:model="selectedSentiment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Sentiments</option>
                    <option value="positive">✓ Positive</option>
                    <option value="neutral">— Neutral</option>
                    <option value="negative">✗ Negative</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">From Date</label>
                <input type="date" wire:model="dateFrom" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">To Date</label>
                <input type="date" wire:model="dateTo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>

        <div class="mt-4 flex justify-between items-center">
            <div class="flex-1">
                <input type="text" wire:model.debounce.300ms="searchTerm" placeholder="Search mentions..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <button wire:click="clearFilters" class="ml-4 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Clear
            </button>
        </div>
    </div>

    {{-- Sentiment Breakdown Chart --}}
    @if(count($sentimentBreakdown) > 0)
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Sentiment Overview</h3>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-3xl font-bold text-green-600">{{ $sentimentBreakdown['positive'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">Positive</div>
                <div class="mt-2 bg-green-100 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $totalMentions > 0 ? round((($sentimentBreakdown['positive'] ?? 0) / $totalMentions) * 100) : 0 }}%"></div>
                </div>
            </div>
            <div>
                <div class="text-3xl font-bold text-gray-600">{{ $sentimentBreakdown['neutral'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">Neutral</div>
                <div class="mt-2 bg-gray-100 rounded-full h-2">
                    <div class="bg-gray-600 h-2 rounded-full" style="width: {{ $totalMentions > 0 ? round((($sentimentBreakdown['neutral'] ?? 0) / $totalMentions) * 100) : 0 }}%"></div>
                </div>
            </div>
            <div>
                <div class="text-3xl font-bold text-red-600">{{ $sentimentBreakdown['negative'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">Negative</div>
                <div class="mt-2 bg-red-100 rounded-full h-2">
                    <div class="bg-red-600 h-2 rounded-full" style="width: {{ $totalMentions > 0 ? round((($sentimentBreakdown['negative'] ?? 0) / $totalMentions) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Mentions List --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Your Mentions</h3>
        </div>
        <ul class="divide-y divide-gray-200">
            @forelse($mentions as $mention)
            <li class="px-6 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 capitalize">
                                {{ str_replace('_', ' ', $mention->platform) }}
                            </span>
                            @if($mention->sentiment === 'positive')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Positive
                            </span>
                            @elseif($mention->sentiment === 'negative')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                ✗ Negative
                            </span>
                            @elseif($mention->sentiment === 'neutral')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                — Neutral
                            </span>
                            @endif
                            <span class="text-sm text-gray-500">{{ $mention->posted_at->format('M d, Y') }} • {{ $mention->posted_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-base text-gray-900 mb-2">{{ Str::limit($mention->mention_text, 300) }}</p>
                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                            <span class="flex items-center">
                                <svg class="mr-1.5 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                {{ $mention->author ?? 'Unknown' }}
                            </span>
                            @if($mention->url)
                            <a href="{{ $mention->url }}" target="_blank" class="flex items-center text-indigo-600 hover:text-indigo-900">
                                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                View Source
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </li>
            @empty
            <li class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No mentions found</h3>
                <p class="mt-1 text-sm text-gray-500">We're actively monitoring for brand mentions. Check back soon!</p>
            </li>
            @endforelse
        </ul>

        @if($mentions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $mentions->links() }}
        </div>
        @endif
    </div>

    {{-- Help Text --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-700">
                    We monitor news sites, social media, reviews, and the web for mentions of your brand. Mentions are updated automatically throughout the day.
                </p>
            </div>
        </div>
    </div>
</div>

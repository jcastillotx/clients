<div class="space-y-6">
    {{-- Pricing Strategy --}}
    @if($analysis->pricing_strategy)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pricing Strategy
            </h4>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                @if(is_array($analysis->pricing_strategy))
                    @if(isset($analysis->pricing_strategy['model']))
                        <div class="mb-3">
                            <span class="text-xs text-gray-500">Pricing Model</span>
                            <p class="text-sm font-medium text-gray-900">{{ $analysis->pricing_strategy['model'] }}</p>
                        </div>
                    @endif
                    @if(isset($analysis->pricing_strategy['tiers']) && is_array($analysis->pricing_strategy['tiers']))
                        <div class="mb-3">
                            <span class="text-xs text-gray-500">Pricing Tiers</span>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach($analysis->pricing_strategy['tiers'] as $tier)
                                    <span class="px-3 py-1 bg-white border border-green-200 rounded text-sm text-gray-700">
                                        {{ is_array($tier) ? json_encode($tier) : $tier }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(isset($analysis->pricing_strategy['competitive_pricing']))
                        <div>
                            <span class="text-xs text-gray-500">Competitive Positioning</span>
                            <p class="text-sm text-gray-700">{{ $analysis->pricing_strategy['competitive_pricing'] }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-700">{{ $analysis->pricing_strategy }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Marketing Channels --}}
    @if($analysis->marketing_channels && count($analysis->marketing_channels) > 0)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                Marketing Channels
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($analysis->marketing_channels as $channel)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                        <span class="text-sm font-medium text-blue-800">{{ is_array($channel) ? ($channel['name'] ?? json_encode($channel)) : $channel }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Target Audience --}}
    @if($analysis->target_audience)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Target Audience
            </h4>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                @if(is_array($analysis->target_audience))
                    @if(isset($analysis->target_audience['primary_segments']))
                        <div class="mb-3">
                            <span class="text-xs text-gray-500">Primary Segments</span>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach((array)$analysis->target_audience['primary_segments'] as $segment)
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">{{ $segment }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(isset($analysis->target_audience['company_sizes']))
                        <div class="mb-3">
                            <span class="text-xs text-gray-500">Company Sizes (B2B)</span>
                            <p class="text-sm text-gray-700">{{ is_array($analysis->target_audience['company_sizes']) ? implode(', ', $analysis->target_audience['company_sizes']) : $analysis->target_audience['company_sizes'] }}</p>
                        </div>
                    @endif
                    @if(isset($analysis->target_audience['use_cases']) && is_array($analysis->target_audience['use_cases']))
                        <div>
                            <span class="text-xs text-gray-500">Common Use Cases</span>
                            <ul class="mt-1 space-y-1">
                                @foreach($analysis->target_audience['use_cases'] as $useCase)
                                    <li class="text-sm text-gray-700">{{ $useCase }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-700">{{ $analysis->target_audience }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Online Presence --}}
    @if($analysis->online_presence)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                Online Presence
            </h4>
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                @if(is_array($analysis->online_presence))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($analysis->online_presence as $key => $value)
                            @if($value)
                                <div>
                                    <span class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $key) }}</span>
                                    @if(is_array($value))
                                        <div class="mt-1">
                                            @foreach($value as $k => $v)
                                                <p class="text-sm text-gray-700">{{ is_string($k) ? "$k: " : '' }}{{ is_array($v) ? json_encode($v) : $v }}</p>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm font-medium text-gray-900">{{ $value }}</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-700">{{ $analysis->online_presence }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Content Strategy --}}
    @if($analysis->content_strategy)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 text-pink-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                Content Strategy
            </h4>
            <div class="bg-pink-50 border border-pink-200 rounded-lg p-4">
                @if(is_array($analysis->content_strategy))
                    @foreach($analysis->content_strategy as $key => $value)
                        @if($value)
                            <div class="mb-3 last:mb-0">
                                <span class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $key) }}</span>
                                @if(is_array($value))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($value as $item)
                                            <span class="px-2 py-0.5 bg-pink-100 text-pink-800 rounded text-xs">{{ is_array($item) ? json_encode($item) : $item }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-700">{{ $value }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @else
                    <p class="text-sm text-gray-700">{{ $analysis->content_strategy }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Customer Reviews Summary --}}
    @if($analysis->customer_reviews)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                Customer Reviews
            </h4>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                @if(is_array($analysis->customer_reviews))
                    @if(isset($analysis->customer_reviews['overall_sentiment']))
                        <div class="mb-3">
                            <span class="text-xs text-gray-500">Overall Sentiment</span>
                            <p class="text-sm font-medium capitalize
                                {{ $analysis->customer_reviews['overall_sentiment'] === 'positive' ? 'text-green-600' : '' }}
                                {{ $analysis->customer_reviews['overall_sentiment'] === 'negative' ? 'text-red-600' : '' }}
                                {{ $analysis->customer_reviews['overall_sentiment'] === 'mixed' ? 'text-yellow-600' : '' }}">
                                {{ $analysis->customer_reviews['overall_sentiment'] }}
                            </p>
                        </div>
                    @endif
                    @if(isset($analysis->customer_reviews['common_praise']) && is_array($analysis->customer_reviews['common_praise']))
                        <div class="mb-3">
                            <span class="text-xs text-gray-500">Common Praise</span>
                            <ul class="mt-1 space-y-1">
                                @foreach($analysis->customer_reviews['common_praise'] as $praise)
                                    <li class="text-sm text-green-700 flex items-start">
                                        <svg class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $praise }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(isset($analysis->customer_reviews['common_complaints']) && is_array($analysis->customer_reviews['common_complaints']))
                        <div>
                            <span class="text-xs text-gray-500">Common Complaints</span>
                            <ul class="mt-1 space-y-1">
                                @foreach($analysis->customer_reviews['common_complaints'] as $complaint)
                                    <li class="text-sm text-red-700 flex items-start">
                                        <svg class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $complaint }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-700">{{ $analysis->customer_reviews }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Technology Stack --}}
    @if($analysis->technology_stack && count($analysis->technology_stack) > 0)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                Technology Stack
            </h4>
            <div class="flex flex-wrap gap-2">
                @foreach($analysis->technology_stack as $tech)
                    <span class="px-3 py-1 bg-gray-100 border border-gray-200 text-gray-700 rounded-full text-sm">
                        {{ is_array($tech) ? json_encode($tech) : $tech }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>

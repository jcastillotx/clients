<div class="space-y-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <p class="text-sm text-blue-800">
                <strong>Strategic Recommendations</strong> are AI-generated suggestions based on the competitor analysis to help improve your competitive positioning.
            </p>
        </div>
    </div>

    @if($analysis->recommendations && count($analysis->recommendations) > 0)
        @php
            $recommendationCategories = [
                'immediate_actions' => ['title' => 'Immediate Actions (0-30 days)', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'red', 'badge' => 'Urgent'],
                'short_term_strategies' => ['title' => 'Short-Term Strategies (1-3 months)', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'yellow', 'badge' => 'Soon'],
                'long_term_opportunities' => ['title' => 'Long-Term Opportunities (6+ months)', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'color' => 'blue', 'badge' => 'Strategic'],
                'differentiation_opportunities' => ['title' => 'Differentiation Opportunities', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'color' => 'purple', 'badge' => 'Unique'],
                'market_positioning' => ['title' => 'Market Positioning', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', 'color' => 'green', 'badge' => 'Position'],
                'messaging_strategies' => ['title' => 'Messaging Strategies', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'color' => 'indigo', 'badge' => 'Message'],
                'product_opportunities' => ['title' => 'Product Opportunities', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'pink', 'badge' => 'Product'],
                'pricing_recommendations' => ['title' => 'Pricing Recommendations', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'orange', 'badge' => 'Pricing'],
                'quick_recommendation' => ['title' => 'Quick Recommendation', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'blue', 'badge' => 'Quick'],
            ];
            $colorClasses = [
                'red' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'badge' => 'bg-red-100 text-red-800'],
                'yellow' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-800', 'badge' => 'bg-yellow-100 text-yellow-800'],
                'blue' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'badge' => 'bg-blue-100 text-blue-800'],
                'purple' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-800', 'badge' => 'bg-purple-100 text-purple-800'],
                'green' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-800', 'badge' => 'bg-green-100 text-green-800'],
                'indigo' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-800', 'badge' => 'bg-indigo-100 text-indigo-800'],
                'pink' => ['bg' => 'bg-pink-50', 'border' => 'border-pink-200', 'text' => 'text-pink-800', 'badge' => 'bg-pink-100 text-pink-800'],
                'orange' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-800', 'badge' => 'bg-orange-100 text-orange-800'],
            ];
        @endphp

        @foreach($recommendationCategories as $key => $config)
            @if(isset($analysis->recommendations[$key]))
                @php
                    $items = $analysis->recommendations[$key];
                    $colors = $colorClasses[$config['color']] ?? $colorClasses['blue'];
                @endphp
                <div class="{{ $colors['bg'] }} {{ $colors['border'] }} border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 {{ $colors['text'] }} mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                            </svg>
                            <h4 class="font-semibold {{ $colors['text'] }}">{{ $config['title'] }}</h4>
                        </div>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $colors['badge'] }}">
                            {{ $config['badge'] }}
                        </span>
                    </div>
                    @if(is_array($items))
                        <ul class="space-y-2">
                            @foreach($items as $index => $item)
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full {{ $colors['badge'] }} flex items-center justify-center text-xs font-medium mr-2">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-sm {{ $colors['text'] }}">{{ is_array($item) ? json_encode($item) : $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm {{ $colors['text'] }}">{{ $items }}</p>
                    @endif
                </div>
            @endif
        @endforeach

        {{-- Raw recommendations if not categorized --}}
        @if(isset($analysis->recommendations['raw']))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">Additional Recommendations</h4>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $analysis->recommendations['raw'] }}</p>
            </div>
        @endif
    @else
        <div class="bg-gray-50 rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No recommendations yet</h3>
            <p class="mt-1 text-sm text-gray-500">Run a full analysis to generate strategic recommendations.</p>
        </div>
    @endif

    {{-- Action Summary --}}
    @if($analysis->recommendations && count($analysis->recommendations) > 0)
        @php
            $totalRecommendations = 0;
            foreach($analysis->recommendations as $key => $recs) {
                if(is_array($recs) && $key !== 'raw') {
                    $totalRecommendations += count($recs);
                }
            }
        @endphp
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-green-800">Total Strategic Recommendations</h4>
                    <p class="text-xs text-green-600 mt-1">Actionable insights to improve your competitive position</p>
                </div>
                <div class="text-3xl font-bold text-green-600">{{ $totalRecommendations }}</div>
            </div>
        </div>
    @endif
</div>

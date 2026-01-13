<div class="space-y-6">
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-sm text-orange-800">
                <strong>Gaps & Limitations</strong> represent areas where the competitor has weaknesses that could be exploited for competitive advantage.
            </p>
        </div>
    </div>

    @if($analysis->gaps_limitations && count($analysis->gaps_limitations) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @php
                $gapCategories = [
                    'product_gaps' => ['title' => 'Product Gaps', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'purple'],
                    'service_gaps' => ['title' => 'Service Gaps', 'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z', 'color' => 'blue'],
                    'market_gaps' => ['title' => 'Market Gaps', 'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green'],
                    'technology_gaps' => ['title' => 'Technology Gaps', 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color' => 'indigo'],
                    'customer_experience_gaps' => ['title' => 'Customer Experience Gaps', 'icon' => 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'pink'],
                    'pricing_limitations' => ['title' => 'Pricing Limitations', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'yellow'],
                    'geographic_limitations' => ['title' => 'Geographic Limitations', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'red'],
                    'competitive_vulnerabilities' => ['title' => 'Competitive Vulnerabilities', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'orange'],
                    'operational_limitations' => ['title' => 'Operational Limitations', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'gray'],
                    'main_gaps' => ['title' => 'Main Gaps', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'red'],
                ];
                $colorClasses = [
                    'purple' => 'bg-purple-50 border-purple-200 text-purple-800',
                    'blue' => 'bg-blue-50 border-blue-200 text-blue-800',
                    'green' => 'bg-green-50 border-green-200 text-green-800',
                    'indigo' => 'bg-indigo-50 border-indigo-200 text-indigo-800',
                    'pink' => 'bg-pink-50 border-pink-200 text-pink-800',
                    'yellow' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                    'red' => 'bg-red-50 border-red-200 text-red-800',
                    'orange' => 'bg-orange-50 border-orange-200 text-orange-800',
                    'gray' => 'bg-gray-50 border-gray-200 text-gray-800',
                ];
            @endphp

            @foreach($gapCategories as $key => $config)
                @if(isset($analysis->gaps_limitations[$key]) && is_array($analysis->gaps_limitations[$key]) && count($analysis->gaps_limitations[$key]) > 0)
                    <div class="border rounded-lg p-4 {{ $colorClasses[$config['color']] ?? 'bg-gray-50 border-gray-200' }}">
                        <div class="flex items-center mb-3">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                            </svg>
                            <h4 class="font-semibold">{{ $config['title'] }}</h4>
                            <span class="ml-auto text-xs font-medium px-2 py-0.5 rounded-full bg-white bg-opacity-50">
                                {{ count($analysis->gaps_limitations[$key]) }}
                            </span>
                        </div>
                        <ul class="space-y-2">
                            @foreach($analysis->gaps_limitations[$key] as $gap)
                                <li class="flex items-start text-sm">
                                    <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5 opacity-70" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ is_array($gap) ? json_encode($gap) : $gap }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Raw gaps if not categorized --}}
        @if(isset($analysis->gaps_limitations['raw']))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">Analysis Notes</h4>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $analysis->gaps_limitations['raw'] }}</p>
            </div>
        @endif
    @else
        <div class="bg-gray-50 rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No gaps identified</h3>
            <p class="mt-1 text-sm text-gray-500">Run a full analysis to identify competitor gaps and limitations.</p>
        </div>
    @endif

    {{-- Summary Stats --}}
    @if($analysis->gaps_limitations && count($analysis->gaps_limitations) > 0)
        @php
            $totalGaps = 0;
            foreach($analysis->gaps_limitations as $key => $gaps) {
                if(is_array($gaps) && $key !== 'raw') {
                    $totalGaps += count($gaps);
                }
            }
        @endphp
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-red-800">Total Gaps & Limitations Identified</h4>
                    <p class="text-xs text-red-600 mt-1">These represent potential opportunities for your competitive positioning</p>
                </div>
                <div class="text-3xl font-bold text-red-600">{{ $totalGaps }}</div>
            </div>
        </div>
    @endif
</div>

<div class="space-y-6">
    {{-- Summary --}}
    @if($analysis->analysis_summary)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-800 mb-2">Analysis Summary</h4>
            <p class="text-sm text-blue-700">{{ $analysis->analysis_summary }}</p>
        </div>
    @endif

    {{-- Company Overview --}}
    @if($analysis->company_overview)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Company Overview</h4>
            <div class="bg-gray-50 rounded-lg p-4">
                @if(is_array($analysis->company_overview))
                    @if(isset($analysis->company_overview['description']))
                        <p class="text-sm text-gray-700 mb-4">{{ $analysis->company_overview['description'] }}</p>
                    @endif
                    @if(isset($analysis->company_overview['summary']))
                        <p class="text-sm text-gray-700 mb-4">{{ $analysis->company_overview['summary'] }}</p>
                    @endif
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @if(isset($analysis->company_overview['founded']))
                            <div>
                                <span class="text-xs text-gray-500">Founded</span>
                                <p class="text-sm font-medium text-gray-900">{{ $analysis->company_overview['founded'] }}</p>
                            </div>
                        @endif
                        @if(isset($analysis->company_overview['headquarters']))
                            <div>
                                <span class="text-xs text-gray-500">Headquarters</span>
                                <p class="text-sm font-medium text-gray-900">{{ $analysis->company_overview['headquarters'] }}</p>
                            </div>
                        @endif
                        @if(isset($analysis->company_overview['employee_count']))
                            <div>
                                <span class="text-xs text-gray-500">Employees</span>
                                <p class="text-sm font-medium text-gray-900">{{ $analysis->company_overview['employee_count'] }}</p>
                            </div>
                        @endif
                        @if(isset($analysis->company_overview['revenue_estimate']))
                            <div>
                                <span class="text-xs text-gray-500">Revenue</span>
                                <p class="text-sm font-medium text-gray-900">{{ $analysis->company_overview['revenue_estimate'] }}</p>
                            </div>
                        @endif
                        @if(isset($analysis->company_overview['funding']))
                            <div>
                                <span class="text-xs text-gray-500">Funding</span>
                                <p class="text-sm font-medium text-gray-900">{{ $analysis->company_overview['funding'] }}</p>
                            </div>
                        @endif
                    </div>
                    @if(isset($analysis->company_overview['key_executives']) && is_array($analysis->company_overview['key_executives']))
                        <div class="mt-4">
                            <span class="text-xs text-gray-500">Key Executives</span>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach($analysis->company_overview['key_executives'] as $exec)
                                    <span class="px-2 py-1 bg-white border border-gray-200 rounded text-xs text-gray-700">{{ is_array($exec) ? ($exec['name'] ?? json_encode($exec)) : $exec }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-700">{{ $analysis->company_overview }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Products & Services --}}
    @if($analysis->products_services && count($analysis->products_services) > 0)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Products & Services</h4>
            <div class="grid gap-4">
                @foreach($analysis->products_services as $product)
                    <div class="bg-gray-50 rounded-lg p-4">
                        @if(is_array($product))
                            <h5 class="font-medium text-gray-900">{{ $product['name'] ?? 'Product' }}</h5>
                            @if(isset($product['description']))
                                <p class="text-sm text-gray-600 mt-1">{{ $product['description'] }}</p>
                            @endif
                            @if(isset($product['pricing']))
                                <p class="text-sm text-blue-600 mt-2">Pricing: {{ $product['pricing'] }}</p>
                            @endif
                            @if(isset($product['key_features']) && is_array($product['key_features']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($product['key_features'] as $feature)
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">{{ $feature }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="text-sm text-gray-700">{{ $product }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Market Position --}}
    @if($analysis->market_position)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Market Position</h4>
            <div class="bg-gray-50 rounded-lg p-4">
                @if(is_array($analysis->market_position))
                    @foreach($analysis->market_position as $key => $value)
                        @if($value)
                            <div class="mb-3 last:mb-0">
                                <span class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $key) }}</span>
                                @if(is_array($value))
                                    <ul class="mt-1 space-y-1">
                                        @foreach($value as $item)
                                            <li class="text-sm text-gray-700">{{ is_array($item) ? json_encode($item) : $item }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm font-medium text-gray-900">{{ $value }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @else
                    <p class="text-sm text-gray-700">{{ $analysis->market_position }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Competitive Advantages --}}
    @if($analysis->competitive_advantages && count($analysis->competitive_advantages) > 0)
        <div>
            <h4 class="text-lg font-semibold text-gray-900 mb-3">Competitive Advantages</h4>
            <ul class="space-y-2">
                @foreach($analysis->competitive_advantages as $advantage)
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm text-gray-700">{{ is_array($advantage) ? json_encode($advantage) : $advantage }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

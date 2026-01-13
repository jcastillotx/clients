<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Strengths --}}
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center mb-3">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <h4 class="text-lg font-semibold text-green-800">Strengths</h4>
            </div>
            @if($analysis->strengths && count($analysis->strengths) > 0)
                <ul class="space-y-2">
                    @foreach($analysis->strengths as $strength)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-green-900">{{ is_array($strength) ? json_encode($strength) : $strength }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-green-700 italic">No strengths identified</p>
            @endif
        </div>

        {{-- Weaknesses --}}
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center mb-3">
                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <h4 class="text-lg font-semibold text-red-800">Weaknesses</h4>
            </div>
            @if($analysis->weaknesses && count($analysis->weaknesses) > 0)
                <ul class="space-y-2">
                    @foreach($analysis->weaknesses as $weakness)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-red-900">{{ is_array($weakness) ? json_encode($weakness) : $weakness }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-red-700 italic">No weaknesses identified</p>
            @endif
        </div>

        {{-- Opportunities --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center mb-3">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <h4 class="text-lg font-semibold text-blue-800">Opportunities</h4>
            </div>
            @if($analysis->opportunities && count($analysis->opportunities) > 0)
                <ul class="space-y-2">
                    @foreach($analysis->opportunities as $opportunity)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-blue-900">{{ is_array($opportunity) ? json_encode($opportunity) : $opportunity }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-blue-700 italic">No opportunities identified</p>
            @endif
        </div>

        {{-- Threats --}}
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center mb-3">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h4 class="text-lg font-semibold text-yellow-800">Threats</h4>
            </div>
            @if($analysis->threats && count($analysis->threats) > 0)
                <ul class="space-y-2">
                    @foreach($analysis->threats as $threat)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-yellow-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-yellow-900">{{ is_array($threat) ? json_encode($threat) : $threat }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-yellow-700 italic">No threats identified</p>
            @endif
        </div>
    </div>

    {{-- SWOT Summary --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">SWOT Summary</h4>
        <div class="grid grid-cols-4 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-green-600">{{ count($analysis->strengths ?? []) }}</p>
                <p class="text-xs text-gray-500">Strengths</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-600">{{ count($analysis->weaknesses ?? []) }}</p>
                <p class="text-xs text-gray-500">Weaknesses</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-blue-600">{{ count($analysis->opportunities ?? []) }}</p>
                <p class="text-xs text-gray-500">Opportunities</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-yellow-600">{{ count($analysis->threats ?? []) }}</p>
                <p class="text-xs text-gray-500">Threats</p>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Competitor Analysis</h2>
            <p class="text-sm text-gray-600 mt-1">AI-powered competitive intelligence and gap analysis</p>
        </div>
        <button wire:click="startNewAnalysis" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Analysis
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Analyses</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total'] }}</p>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Completed</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['completed'] }}</p>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Processing</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['processing'] }}</p>
                </div>
                <div class="h-12 w-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- New Analysis Form Modal --}}
    @if($showForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click.self="cancelForm">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-lg bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">New Competitor Analysis</h3>
                    <button wire:click="cancelForm" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="runAnalysis" class="space-y-4">
                    <div>
                        <label for="competitorName" class="block text-sm font-medium text-gray-700">Competitor Name *</label>
                        <input type="text" id="competitorName" wire:model="competitorName"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="e.g., Acme Corporation">
                        @error('competitorName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="competitorUrl" class="block text-sm font-medium text-gray-700">Website URL (Optional)</label>
                        <input type="url" id="competitorUrl" wire:model="competitorUrl"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="https://www.example.com">
                        @error('competitorUrl') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="competitorIndustry" class="block text-sm font-medium text-gray-700">Industry (Optional)</label>
                        <input type="text" id="competitorIndustry" wire:model="competitorIndustry"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="e.g., Software, Retail, Healthcare">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Analysis Type</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" wire:model="analysisType" value="full" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Full Analysis (2-3 min)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" wire:model="analysisType" value="quick" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Quick Analysis (30 sec)</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm text-blue-800">
                            <strong>Full Analysis</strong> includes: SWOT analysis, gaps & limitations, market position, pricing strategy, marketing channels, and strategic recommendations.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="cancelForm" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                            wire:loading.attr="disabled" wire:target="runAnalysis">
                            <span wire:loading.remove wire:target="runAnalysis">Run Analysis</span>
                            <span wire:loading wire:target="runAnalysis" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Analyzing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Analyses List --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <input type="text" wire:model.debounce.300ms="searchTerm"
                        placeholder="Search competitors..."
                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <select wire:model="statusFilter" class="mt-2 w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="processing">Processing</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div class="divide-y divide-gray-200 max-h-[600px] overflow-y-auto">
                    @forelse($analyses as $analysis)
                        <div wire:click="viewAnalysis({{ $analysis->id }})"
                            class="p-4 hover:bg-gray-50 cursor-pointer transition-colors {{ $selectedAnalysis === $analysis->id ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 truncate">{{ $analysis->competitor_name }}</h4>
                                    @if($analysis->competitor_url)
                                        <p class="text-xs text-gray-500 truncate">{{ $analysis->competitor_url }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">{{ $analysis->created_at->diffForHumans() }}</p>
                                </div>
                                <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full
                                    {{ $analysis->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $analysis->status === 'processing' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $analysis->status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $analysis->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($analysis->status) }}
                                </span>
                            </div>
                            @if($analysis->confidence_score)
                                <div class="mt-2">
                                    <div class="flex items-center text-xs text-gray-500">
                                        <span>Confidence:</span>
                                        <div class="ml-2 flex-1 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $analysis->confidence_score }}%"></div>
                                        </div>
                                        <span class="ml-2">{{ number_format($analysis->confidence_score, 0) }}%</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No analyses yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by analyzing a competitor.</p>
                        </div>
                    @endforelse
                </div>

                @if($analyses->hasPages())
                    <div class="p-4 border-t border-gray-200">
                        {{ $analyses->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Analysis Detail View --}}
        <div class="lg:col-span-2">
            @if($currentAnalysis)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    {{-- Header --}}
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ $currentAnalysis->competitor_name }}</h3>
                                @if($currentAnalysis->competitor_url)
                                    <a href="{{ $currentAnalysis->competitor_url }}" target="_blank" class="text-sm text-blue-600 hover:underline">
                                        {{ $currentAnalysis->competitor_url }}
                                    </a>
                                @endif
                                <div class="flex items-center mt-2 space-x-4 text-sm text-gray-500">
                                    <span>Analyzed {{ $currentAnalysis->analyzed_at?->diffForHumans() ?? 'N/A' }}</span>
                                    @if($currentAnalysis->processing_time_ms)
                                        <span>{{ number_format($currentAnalysis->processing_time_ms / 1000, 1) }}s processing</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button wire:click="deleteAnalysis({{ $currentAnalysis->id }})"
                                    onclick="return confirm('Are you sure you want to delete this analysis?')"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px overflow-x-auto">
                            @foreach(['overview' => 'Overview', 'swot' => 'SWOT', 'gaps' => 'Gaps & Limitations', 'marketing' => 'Marketing Intel', 'recommendations' => 'Recommendations'] as $tab => $label)
                                <button wire:click="setTab('{{ $tab }}')"
                                    class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                                    {{ $activeTab === $tab ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    {{-- Tab Content --}}
                    <div class="p-6">
                        @if($activeTab === 'overview')
                            @include('livewire.marketing.partials.competitor-overview', ['analysis' => $currentAnalysis])
                        @elseif($activeTab === 'swot')
                            @include('livewire.marketing.partials.competitor-swot', ['analysis' => $currentAnalysis])
                        @elseif($activeTab === 'gaps')
                            @include('livewire.marketing.partials.competitor-gaps', ['analysis' => $currentAnalysis])
                        @elseif($activeTab === 'marketing')
                            @include('livewire.marketing.partials.competitor-marketing', ['analysis' => $currentAnalysis])
                        @elseif($activeTab === 'recommendations')
                            @include('livewire.marketing.partials.competitor-recommendations', ['analysis' => $currentAnalysis])
                        @endif
                    </div>

                    {{-- Sources --}}
                    @if($currentAnalysis->sources && count($currentAnalysis->sources) > 0)
                        <div class="p-6 border-t border-gray-200 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Sources</h4>
                            <ul class="text-xs text-gray-600 space-y-1">
                                @foreach($currentAnalysis->sources as $source)
                                    <li class="truncate">
                                        <a href="{{ $source }}" target="_blank" class="text-blue-600 hover:underline">{{ $source }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Select an analysis</h3>
                    <p class="mt-2 text-sm text-gray-500">Choose an existing analysis from the list or create a new one.</p>
                    <button wire:click="startNewAnalysis" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Analysis
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

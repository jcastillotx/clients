<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Brand Monitoring</h2>
            <p class="text-sm text-gray-600 mt-1">Track mentions and sentiment across the web</p>
        </div>
        <button wire:click="fetchNewMentions" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Fetch New Mentions
        </button>
    </div>

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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Mentions</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_mentions']) }}</p>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Positive</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($stats['positive_mentions']) }}</p>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Neutral</p>
                    <p class="text-3xl font-bold text-gray-600 mt-2">{{ number_format($stats['neutral_mentions']) }}</p>
                </div>
                <div class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Negative</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format($stats['negative_mentions']) }}</p>
                    @if($stats['unresponded_negative'] > 0)
                        <p class="text-xs text-red-500 mt-1">{{ $stats['unresponded_negative'] }} need response</p>
                    @endif
                </div>
                <div class="h-12 w-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sentiment Trend</h3>
            <div class="h-64">
                @if(count($sentimentTrend) > 0)
                    <canvas id="sentimentChart"></canvas>
                @else
                    <div class="flex items-center justify-center h-full text-gray-400">
                        <p>No data available for this period</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Breakdown</h3>
            <div class="space-y-3">
                @forelse($platformBreakdown as $platform)
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700 capitalize">{{ $platform['platform'] }}</span>
                            <span class="text-sm text-gray-600">{{ $platform['count'] }} mentions</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($platform['count'] / $stats['total_mentions']) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-8">No platform data available</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div class="flex items-center space-x-4">
                    <select wire:model="dateRange" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="1">Last 24 hours</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>

                    <select wire:model="platformFilter" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">All Platforms</option>
                        <option value="news">News</option>
                        <option value="reddit">Reddit</option>
                        <option value="twitter">Twitter</option>
                        <option value="youtube">YouTube</option>
                        <option value="google">Google</option>
                    </select>

                    <select wire:model="sentimentFilter" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">All Sentiment</option>
                        <option value="positive">Positive</option>
                        <option value="neutral">Neutral</option>
                        <option value="negative">Negative</option>
                    </select>
                </div>

                <div class="flex-1 md:max-w-md">
                    <input wire:model.debounce.500ms="searchTerm" type="text" placeholder="Search mentions..." class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <div class="divide-y divide-gray-200">
            @forelse($mentions as $mention)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full capitalize
                                    {{ $mention->sentiment === 'positive' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $mention->sentiment === 'neutral' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $mention->sentiment === 'negative' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $mention->sentiment ?? 'unknown' }}
                                </span>
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full capitalize">
                                    {{ $mention->platform }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $mention->published_at->diffForHumans() }}
                                </span>
                            </div>

                            <h4 class="text-base font-semibold text-gray-900 mb-2">
                                <a href="{{ $mention->url }}" target="_blank" class="hover:text-blue-600 transition-colors">
                                    {{ $mention->title }}
                                </a>
                            </h4>

                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                {{ $mention->content }}
                            </p>

                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                @if($mention->author)
                                    <span>By {{ $mention->author }}</span>
                                @endif
                                @if($mention->engagement_score)
                                    <span>Engagement: {{ number_format($mention->engagement_score) }}</span>
                                @endif
                                @if($mention->responded_at)
                                    <span class="text-green-600 font-medium">✓ Responded</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2 ml-4">
                            <a href="{{ $mention->url }}" target="_blank" class="px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition-colors text-center">
                                View
                            </a>
                            @if(!$mention->responded_at)
                                <button wire:click="markAsResponded({{ $mention->id }})" class="px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded hover:bg-green-100 transition-colors">
                                    Mark Responded
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No mentions found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or fetch new mentions.</p>
                </div>
            @endforelse
        </div>

        @if($mentions->hasPages())
            <div class="p-6 border-t border-gray-200">
                {{ $mentions->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sentimentData = @json($sentimentTrend);

            if (sentimentData.length > 0) {
                const ctx = document.getElementById('sentimentChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: sentimentData.map(d => d.date),
                            datasets: [
                                {
                                    label: 'Positive',
                                    data: sentimentData.map(d => d.positive),
                                    borderColor: 'rgb(34, 197, 94)',
                                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                    tension: 0.4
                                },
                                {
                                    label: 'Neutral',
                                    data: sentimentData.map(d => d.neutral),
                                    borderColor: 'rgb(107, 114, 128)',
                                    backgroundColor: 'rgba(107, 114, 128, 0.1)',
                                    tension: 0.4
                                },
                                {
                                    label: 'Negative',
                                    data: sentimentData.map(d => d.negative),
                                    borderColor: 'rgb(239, 68, 68)',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
    @endpush
</div>

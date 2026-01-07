<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Ad Performance</h2>
            <p class="text-sm text-gray-600 mt-1">Track performance across all advertising campaigns</p>
        </div>
    </div>

    <div class="flex items-center space-x-4">
        <select wire:model="selectedCampaignId" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="all">All Campaigns</option>
            @foreach($campaigns as $campaign)
                <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
            @endforeach
        </select>

        <select wire:model="dateRange" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">Impressions</span>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['impressions']) }}</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">Clicks</span>
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['clicks']) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ number_format($stats['ctr'], 2) }}% CTR</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">Conversions</span>
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['conversions']) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ number_format($stats['conversion_rate'], 2) }}% rate</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">Ad Spend</span>
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="text-2xl font-bold text-gray-900">${{ number_format($stats['spend'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">${{ number_format($stats['cpc'], 2) }} CPC</div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">ROAS</span>
                <svg class="w-5 h-5 {{ $stats['roas'] >= 1 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($stats['roas'] >= 1)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    @endif
                </svg>
            </div>
            <div class="text-2xl font-bold {{ $stats['roas'] >= 1 ? 'text-green-600' : 'text-red-600' }}">
                {{ number_format($stats['roas'], 2) }}x
            </div>
            <div class="text-xs text-gray-500 mt-1">${{ number_format($stats['revenue'], 2) }} revenue</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Over Time</h3>
                <div class="h-80">
                    @if(count($chartData) > 0)
                        <canvas id="adPerformanceChart"></canvas>
                    @else
                        <div class="flex items-center justify-center h-full text-gray-400">
                            <p>No data available for this period</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Campaigns</h3>
            <div class="space-y-4">
                @forelse($topCampaigns as $campaign)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900">{{ $campaign['name'] }}</span>
                            <div class="flex items-center space-x-1">
                                <div class="h-3 w-3 rounded-full" style="background-color: {{ $campaign['platform_color'] }}"></div>
                                <span class="text-xs text-gray-600">{{ $campaign['platform'] }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div>
                                <div class="font-medium">ROAS</div>
                                <div class="text-lg font-bold {{ $campaign['roas'] >= 1 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($campaign['roas'], 2) }}x
                                </div>
                            </div>
                            <div>
                                <div class="font-medium">Spend</div>
                                <div class="text-lg font-bold text-gray-900">${{ number_format($campaign['spend'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-8">No campaign data available</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Breakdown</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impressions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conversions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spend</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($platformBreakdown as $platform)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div class="h-3 w-3 rounded-full" style="background-color: {{ $platform['platform_color'] }}"></div>
                                    <span class="text-sm font-medium text-gray-900">{{ $platform['platform'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ number_format($platform['impressions']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ number_format($platform['clicks']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ number_format($platform['conversions']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                ${{ number_format($platform['spend'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                ${{ number_format($platform['revenue'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                No platform data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($chartData);

            if (chartData.length > 0) {
                const ctx = document.getElementById('adPerformanceChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.map(d => d.date),
                            datasets: [
                                {
                                    label: 'Spend',
                                    data: chartData.map(d => d.spend),
                                    borderColor: 'rgb(239, 68, 68)',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    yAxisID: 'y',
                                    tension: 0.4
                                },
                                {
                                    label: 'Revenue',
                                    data: chartData.map(d => d.revenue),
                                    borderColor: 'rgb(34, 197, 94)',
                                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                    yAxisID: 'y',
                                    tension: 0.4
                                },
                                {
                                    label: 'Conversions',
                                    data: chartData.map(d => d.conversions),
                                    borderColor: 'rgb(234, 179, 8)',
                                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                                    yAxisID: 'y1',
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: '$ Spend / Revenue'
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    beginAtZero: true,
                                    grid: {
                                        drawOnChartArea: false,
                                    },
                                    title: {
                                        display: true,
                                        text: 'Conversions'
                                    }
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

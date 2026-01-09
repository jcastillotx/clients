<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Campaigns Dashboard</h1>
                    <p class="text-muted mb-0">Track and monitor your marketing campaigns</p>
                </div>
                <div class="d-flex gap-2">
                    <select wire:model.live="dateRange" class="form-select form-select-sm" style="width: auto;">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills nav-fill" style="max-width: 500px;">
                <li class="nav-item">
                    <button wire:click="setTab('overview')"
                            class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}">
                        <i class="fas fa-chart-pie mr-1"></i> Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button wire:click="setTab('campaigns')"
                            class="nav-link {{ $activeTab === 'campaigns' ? 'active' : '' }}">
                        <i class="fas fa-bullhorn mr-1"></i> Campaigns
                    </button>
                </li>
                @if($activeTab === 'detail' && $selectedCampaignId)
                <li class="nav-item">
                    <button class="nav-link active">
                        <i class="fas fa-info-circle mr-1"></i> Details
                    </button>
                </li>
                @endif
            </ul>
        </div>
    </div>

    {{-- Overview Tab --}}
    @if($activeTab === 'overview')
        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card h-100 border-left-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Campaigns</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $overviewStats['active_campaigns'] }}</div>
                                <small class="text-muted">of {{ $overviewStats['total_campaigns'] }} total</small>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-bullhorn fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card h-100 border-left-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Spend</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">${{ number_format($overviewStats['total_spend'], 2) }}</div>
                                <small class="text-muted">of ${{ number_format($overviewStats['total_budget'], 2) }} budget</small>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card h-100 border-left-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Conversions</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($overviewStats['conversions']) }}</div>
                                <small class="text-muted">{{ number_format($overviewStats['clicks']) }} clicks</small>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-chart-line fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card h-100 border-left-{{ $overviewStats['roi'] >= 0 ? 'success' : 'danger' }} shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-xs font-weight-bold text-{{ $overviewStats['roi'] >= 0 ? 'success' : 'danger' }} text-uppercase mb-1">ROI</div>
                                <div class="h4 mb-0 font-weight-bold text-{{ $overviewStats['roi'] >= 0 ? 'success' : 'danger' }}">
                                    {{ $overviewStats['roi'] >= 0 ? '+' : '' }}{{ $overviewStats['roi'] }}%
                                </div>
                                <small class="text-muted">${{ number_format($overviewStats['total_revenue'], 2) }} revenue</small>
                            </div>
                            <div class="text-{{ $overviewStats['roi'] >= 0 ? 'success' : 'danger' }}">
                                <i class="fas fa-{{ $overviewStats['roi'] >= 0 ? 'arrow-up' : 'arrow-down' }} fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts and Top Performers --}}
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Performance Trend</h6>
                    </div>
                    <div class="card-body">
                        @if(count($chartData) > 0)
                            <canvas id="performanceChart" height="300"></canvas>
                        @else
                            <div class="d-flex align-items-center justify-content-center" style="height: 300px;">
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-area fa-3x mb-3 opacity-50"></i>
                                    <p>No performance data available for this period</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Performing Campaigns</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($topCampaigns as $campaign)
                            <div class="p-3 border-bottom {{ !$loop->last ? '' : 'border-bottom-0' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <a href="#" wire:click.prevent="viewCampaign({{ $campaign['id'] }})"
                                           class="font-weight-bold text-primary text-decoration-none">
                                            {{ Str::limit($campaign['name'], 25) }}
                                        </a>
                                        <br>
                                        <span class="badge {{ $this->getTypeBadgeClass($campaign['type']) }} mt-1">
                                            {{ ucfirst($campaign['type']) }}
                                        </span>
                                    </div>
                                    <span class="badge badge-lg {{ $campaign['roi'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                                        {{ $campaign['roi'] >= 0 ? '+' : '' }}{{ $campaign['roi'] }}%
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between text-sm text-muted">
                                    <span>{{ number_format($campaign['conversions']) }} conversions</span>
                                    <span>${{ number_format($campaign['spend'], 2) }} spent</span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-trophy fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">No active campaigns</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Status and Type Distribution --}}
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Campaigns by Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @php
                                $statuses = ['active' => 'success', 'planning' => 'info', 'paused' => 'warning', 'completed' => 'secondary'];
                            @endphp
                            @foreach($statuses as $status => $color)
                                <div class="col-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <div class="bg-{{ $color }} rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <span class="text-white font-weight-bold">{{ $statusCounts[$status] ?? 0 }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-muted text-uppercase">{{ ucfirst($status) }}</div>
                                            <div class="font-weight-bold">{{ $statusCounts[$status] ?? 0 }} campaigns</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Campaigns by Type</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="typeChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Campaigns List Tab --}}
    @if($activeTab === 'campaigns')
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <select wire:model.live="statusFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="planning">Planning</option>
                        <option value="paused">Paused</option>
                        <option value="completed">Completed</option>
                    </select>
                    <select wire:model.live="typeFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">All Types</option>
                        <option value="social">Social</option>
                        <option value="email">Email</option>
                        <option value="ppc">PPC</option>
                        <option value="content">Content</option>
                        <option value="seo">SEO</option>
                        <option value="launch">Launch</option>
                        <option value="event">Event</option>
                        <option value="seasonal">Seasonal</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Campaign</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Dates</th>
                                <th>Budget</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $campaign)
                                <tr>
                                    <td>
                                        <a href="#" wire:click.prevent="viewCampaign({{ $campaign->id }})"
                                           class="font-weight-bold text-primary text-decoration-none">
                                            {{ $campaign->campaign_name }}
                                        </a>
                                        @if($campaign->description)
                                            <br><small class="text-muted">{{ Str::limit($campaign->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $this->getTypeBadgeClass($campaign->campaign_type) }}">
                                            {{ ucfirst($campaign->campaign_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $this->getStatusBadgeClass($campaign->status) }}">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($campaign->start_date)
                                            {{ $campaign->start_date->format('M d') }}
                                            @if($campaign->end_date)
                                                - {{ $campaign->end_date->format('M d, Y') }}
                                            @endif
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($campaign->budget)
                                            ${{ number_format($campaign->budget, 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button wire:click="viewCampaign({{ $campaign->id }})"
                                                class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-bullhorn fa-3x text-muted mb-3 opacity-50"></i>
                                        <p class="text-muted mb-0">No campaigns found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($campaigns->hasPages())
                <div class="card-footer">
                    {{ $campaigns->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Campaign Detail Tab --}}
    @if($activeTab === 'detail' && $selectedCampaignId && !empty($campaignDetail))
        <div class="mb-3">
            <button wire:click="setTab('campaigns')" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Campaigns
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-1">{{ $campaignDetail['name'] }}</h4>
                                <div class="d-flex gap-2 mb-2">
                                    <span class="badge {{ $this->getTypeBadgeClass($campaignDetail['type']) }}">
                                        {{ ucfirst($campaignDetail['type']) }}
                                    </span>
                                    <span class="badge {{ $this->getStatusBadgeClass($campaignDetail['status']) }}">
                                        {{ ucfirst($campaignDetail['status']) }}
                                    </span>
                                </div>
                                @if($campaignDetail['description'])
                                    <p class="text-muted mb-0">{{ $campaignDetail['description'] }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                @if($campaignDetail['budget'])
                                    <div class="h4 text-primary mb-0">${{ number_format($campaignDetail['budget'], 2) }}</div>
                                    <small class="text-muted">Budget</small>
                                @endif
                            </div>
                        </div>
                        <hr>
                        <div class="row text-sm">
                            <div class="col-md-3">
                                <strong>Start Date:</strong> {{ $campaignDetail['start_date'] ?? 'Not set' }}
                            </div>
                            <div class="col-md-3">
                                <strong>End Date:</strong> {{ $campaignDetail['end_date'] ?? 'Not set' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Created:</strong> {{ $campaignDetail['created_at'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Campaign Metrics --}}
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100 bg-light">
                    <div class="card-body text-center py-3">
                        <div class="text-xs text-muted text-uppercase mb-1">Impressions</div>
                        <div class="h4 mb-0">{{ number_format($campaignMetrics['impressions']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100 bg-light">
                    <div class="card-body text-center py-3">
                        <div class="text-xs text-muted text-uppercase mb-1">Clicks</div>
                        <div class="h4 mb-0">{{ number_format($campaignMetrics['clicks']) }}</div>
                        <small class="text-muted">{{ $campaignMetrics['ctr'] }}% CTR</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100 bg-light">
                    <div class="card-body text-center py-3">
                        <div class="text-xs text-muted text-uppercase mb-1">Conversions</div>
                        <div class="h4 mb-0">{{ number_format($campaignMetrics['conversions']) }}</div>
                        <small class="text-muted">{{ $campaignMetrics['conversion_rate'] }}% rate</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100 bg-light">
                    <div class="card-body text-center py-3">
                        <div class="text-xs text-muted text-uppercase mb-1">Spend</div>
                        <div class="h4 mb-0">${{ number_format($campaignMetrics['spend'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100 bg-light">
                    <div class="card-body text-center py-3">
                        <div class="text-xs text-muted text-uppercase mb-1">Revenue</div>
                        <div class="h4 mb-0">${{ number_format($campaignMetrics['revenue'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card h-100 {{ $campaignMetrics['roi'] >= 0 ? 'bg-success-light' : 'bg-danger-light' }}">
                    <div class="card-body text-center py-3">
                        <div class="text-xs text-muted text-uppercase mb-1">ROI</div>
                        <div class="h4 mb-0 text-{{ $campaignMetrics['roi'] >= 0 ? 'success' : 'danger' }}">
                            {{ $campaignMetrics['roi'] >= 0 ? '+' : '' }}{{ $campaignMetrics['roi'] }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Campaign Links --}}
        @if(count($campaignLinks) > 0)
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Campaign Links</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>URL</th>
                                    <th>Source</th>
                                    <th>Medium</th>
                                    <th class="text-center">Clicks</th>
                                    <th class="text-center">Conversions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campaignLinks as $link)
                                    <tr>
                                        <td>
                                            <a href="{{ $link['short_url'] ?? $link['original_url'] }}"
                                               target="_blank" class="text-primary">
                                                {{ Str::limit($link['original_url'], 40) }}
                                                <i class="fas fa-external-link-alt fa-xs ml-1"></i>
                                            </a>
                                        </td>
                                        <td><code>{{ $link['utm_source'] ?? '-' }}</code></td>
                                        <td><code>{{ $link['utm_medium'] ?? '-' }}</code></td>
                                        <td class="text-center">{{ number_format($link['clicks']) }}</td>
                                        <td class="text-center">{{ number_format($link['conversions']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Goals Section --}}
        @if(!empty($campaignDetail['goals']))
            <div class="card shadow-sm mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Campaign Goals</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($campaignDetail['goals'] as $goal)
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-bullseye text-primary mr-2"></i>
                                    <span>{{ $goal }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('livewire:initialized', function() {
    initCharts();

    Livewire.hook('morph.updated', () => {
        initCharts();
    });
});

function initCharts() {
    // Performance Chart
    const perfCtx = document.getElementById('performanceChart');
    if (perfCtx) {
        const chartData = @json($chartData);

        if (window.performanceChartInstance) {
            window.performanceChartInstance.destroy();
        }

        if (chartData.length > 0) {
            window.performanceChartInstance = new Chart(perfCtx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => d.date),
                    datasets: [
                        {
                            label: 'Clicks',
                            data: chartData.map(d => d.clicks),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Conversions',
                            data: chartData.map(d => d.conversions),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
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

    // Type Distribution Chart
    const typeCtx = document.getElementById('typeChart');
    if (typeCtx) {
        const typeCounts = @json($typeCounts);

        if (window.typeChartInstance) {
            window.typeChartInstance.destroy();
        }

        const typeLabels = Object.keys(typeCounts);
        const typeValues = Object.values(typeCounts);
        const typeColors = {
            'social': '#ec4899',
            'email': '#8b5cf6',
            'ppc': '#f97316',
            'content': '#14b8a6',
            'seo': '#6366f1',
            'launch': '#ef4444',
            'event': '#06b6d4',
            'seasonal': '#f59e0b'
        };

        if (typeLabels.length > 0) {
            window.typeChartInstance = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: typeLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                    datasets: [{
                        data: typeValues,
                        backgroundColor: typeLabels.map(l => typeColors[l] || '#6b7280')
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush

@push('styles')
<style>
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.border-left-warning { border-left: 4px solid #f6c23e !important; }
.border-left-danger { border-left: 4px solid #e74a3b !important; }

.bg-success-light { background-color: rgba(28, 200, 138, 0.1) !important; }
.bg-danger-light { background-color: rgba(231, 74, 59, 0.1) !important; }

.nav-pills .nav-link {
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    color: #5a5c69;
}
.nav-pills .nav-link.active {
    background-color: #4e73df;
}
.nav-pills .nav-link:not(.active):hover {
    background-color: #eaecf4;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
@endpush

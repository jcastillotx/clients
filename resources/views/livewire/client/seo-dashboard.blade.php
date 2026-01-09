<div>
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 d-flex align-items-center">
                        <i class="fas fa-search-dollar text-primary mr-3"></i>
                        <span>SEO Dashboard</span>
                        @if($gscConnected)
                            <span class="badge badge-success ml-3"><i class="fab fa-google mr-1"></i> GSC Connected</span>
                        @endif
                    </h1>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex justify-content-end align-items-center">
                        <select wire:model="dateRange" class="form-control mr-2" style="width: auto;">
                            <option value="7">Last 7 days</option>
                            <option value="28">Last 28 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                        @if($gscConnected)
                            <button wire:click="syncFromGsc" class="btn btn-outline-primary mr-2" wire:loading.attr="disabled">
                                <i class="fas fa-sync-alt" wire:loading.class="fa-spin" wire:target="syncFromGsc"></i>
                                Sync Data
                            </button>
                        @endif
                        <div class="input-group" style="width: 300px;">
                            <input type="url" wire:model.defer="websiteUrl" class="form-control" placeholder="https://example.com">
                            <div class="input-group-append">
                                <button wire:click="runPageSpeedAnalysis" class="btn btn-primary" wire:loading.attr="disabled">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="runPageSpeedAnalysis, runSecurityScan, syncFromGsc" class="position-fixed w-100 h-100 d-flex align-items-center justify-content-center" style="top:0;left:0;background:rgba(0,0,0,0.3);z-index:9999;">
        <div class="bg-white rounded-lg p-4 shadow-lg text-center">
            <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <p class="mb-0 font-weight-bold">Analyzing...</p>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="container-fluid">
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <a wire:click.prevent="setTab('overview')" class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" href="#">
                    <i class="fas fa-chart-pie mr-1"></i> Overview
                </a>
            </li>
            <li class="nav-item">
                <a wire:click.prevent="setTab('keywords')" class="nav-link {{ $activeTab === 'keywords' ? 'active' : '' }}" href="#">
                    <i class="fas fa-key mr-1"></i> Keywords
                </a>
            </li>
            <li class="nav-item">
                <a wire:click.prevent="setTab('pagespeed')" class="nav-link {{ $activeTab === 'pagespeed' ? 'active' : '' }}" href="#">
                    <i class="fas fa-tachometer-alt mr-1"></i> Page Speed
                </a>
            </li>
            <li class="nav-item">
                <a wire:click.prevent="setTab('security')" class="nav-link {{ $activeTab === 'security' ? 'active' : '' }}" href="#">
                    <i class="fas fa-shield-alt mr-1"></i> Security
                </a>
            </li>
        </ul>

        {{-- ==================== OVERVIEW TAB ==================== --}}
        @if($activeTab === 'overview')
        <div class="row">
            {{-- Overall Score Gauge --}}
            <div class="col-lg-3 col-md-6">
                <div class="card bg-gradient-dark">
                    <div class="card-body text-center text-white">
                        <h6 class="text-uppercase mb-3">Overall SEO Score</h6>
                        <div class="position-relative d-inline-block">
                            <canvas id="overallScoreGauge" width="150" height="150"></canvas>
                            <div class="position-absolute w-100 text-center" style="top: 55%; left: 0; transform: translateY(-50%);">
                                <span class="display-4 font-weight-bold">{{ $overallScore }}</span>
                            </div>
                        </div>
                        <p class="mt-2 mb-0">
                            @if($overallScore >= 80) <span class="text-success"><i class="fas fa-check-circle"></i> Excellent</span>
                            @elseif($overallScore >= 60) <span class="text-warning"><i class="fas fa-exclamation-circle"></i> Good</span>
                            @else <span class="text-danger"><i class="fas fa-times-circle"></i> Needs Work</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Key Metrics --}}
            <div class="col-lg-9 col-md-6">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-key"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Keywords</span>
                                <span class="info-box-number">{{ number_format($keywordStats['total']) }}</span>
                                <span class="progress-description">Tracked</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Top 10</span>
                                <span class="info-box-number">{{ $keywordStats['top_10'] }}</span>
                                <span class="progress-description">Keywords</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="info-box bg-gradient-warning">
                            <span class="info-box-icon"><i class="fas fa-sort-amount-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Avg Position</span>
                                <span class="info-box-number">{{ $keywordStats['avg_position'] ?: '—' }}</span>
                                <span class="progress-description">Google</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="info-box bg-gradient-primary">
                            <span class="info-box-icon"><i class="fas fa-mouse-pointer"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Clicks</span>
                                <span class="info-box-number">{{ number_format($keywordStats['total_clicks']) }}</span>
                                <span class="progress-description">{{ $keywordStats['avg_ctr'] }}% CTR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Position Distribution Chart --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Ranking Distribution</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="positionDistributionChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            {{-- Clicks & Impressions Trend --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Performance Trend</h3>
                    </div>
                    <div class="card-body">
                        @if(count($dailyTrendData) > 0)
                            <canvas id="performanceTrendChart" height="100"></canvas>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-chart-line fa-3x mb-3"></i>
                                <p>Connect Google Search Console to see performance trends</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Device Breakdown --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="card-title"><i class="fas fa-mobile-alt mr-2"></i>Traffic by Device</h3>
                    </div>
                    <div class="card-body">
                        @if(count($deviceData) > 0)
                            @foreach($deviceData as $device)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <i class="fas fa-{{ $device['device'] === 'MOBILE' ? 'mobile-alt' : ($device['device'] === 'TABLET' ? 'tablet-alt' : 'desktop') }} mr-2 text-primary"></i>
                                    {{ ucfirst(strtolower($device['device'])) }}
                                </div>
                                <div class="text-right">
                                    <strong>{{ number_format($device['clicks']) }}</strong> clicks
                                    <br><small class="text-muted">Pos: {{ round($device['position'], 1) }}</small>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <p class="text-muted text-center mb-0">No device data available</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Top Pages --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Top Performing Pages</h3>
                    </div>
                    <div class="card-body p-0">
                        @if(count($topPagesData) > 0)
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Page</th>
                                    <th class="text-right">Clicks</th>
                                    <th class="text-right">Impressions</th>
                                    <th class="text-right">CTR</th>
                                    <th class="text-right">Position</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($topPagesData, 0, 5) as $page)
                                <tr>
                                    <td class="text-truncate" style="max-width: 300px;" title="{{ $page['page'] }}">
                                        <i class="fas fa-file text-muted mr-2"></i>
                                        {{ parse_url($page['page'], PHP_URL_PATH) ?: '/' }}
                                    </td>
                                    <td class="text-right"><strong>{{ number_format($page['clicks']) }}</strong></td>
                                    <td class="text-right">{{ number_format($page['impressions']) }}</td>
                                    <td class="text-right">{{ $page['ctr'] }}%</td>
                                    <td class="text-right">
                                        <span class="badge badge-{{ $page['position'] <= 10 ? 'success' : ($page['position'] <= 20 ? 'warning' : 'secondary') }}">
                                            {{ round($page['position'], 1) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                            <p class="text-muted text-center py-4 mb-0">No page data available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ==================== KEYWORDS TAB ==================== --}}
        @if($activeTab === 'keywords')
        <div class="row mb-4">
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-info">
                    <div class="inner">
                        <h3>{{ $keywordStats['total'] }}</h3>
                        <p>Total Keywords</p>
                    </div>
                    <div class="icon"><i class="fas fa-key"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-success">
                    <div class="inner">
                        <h3>{{ $keywordStats['top_3'] }}</h3>
                        <p>Top 3</p>
                    </div>
                    <div class="icon"><i class="fas fa-medal"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-primary">
                    <div class="inner">
                        <h3>{{ $keywordStats['top_10'] }}</h3>
                        <p>Top 10</p>
                    </div>
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-warning">
                    <div class="inner">
                        <h3>{{ $keywordStats['top_20'] }}</h3>
                        <p>Top 20</p>
                    </div>
                    <div class="icon"><i class="fas fa-star"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-secondary">
                    <div class="inner">
                        <h3>{{ $keywordStats['avg_position'] ?: '—' }}</h3>
                        <p>Avg Position</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-dark">
                    <div class="inner">
                        <h3>{{ number_format($keywordStats['total_impressions']) }}</h3>
                        <p>Impressions</p>
                    </div>
                    <div class="icon"><i class="fas fa-eye"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Keyword Rankings</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Keyword</th>
                            <th class="text-center">Position</th>
                            <th class="text-right">Clicks</th>
                            <th class="text-right">Impressions</th>
                            <th class="text-right">CTR</th>
                            <th class="text-center">Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($keywords as $keyword)
                        <tr>
                            <td>
                                <strong>{{ $keyword->keyword }}</strong>
                            </td>
                            <td class="text-center">
                                @if($keyword->current_position)
                                    <span class="badge badge-pill badge-{{ $keyword->current_position <= 3 ? 'success' : ($keyword->current_position <= 10 ? 'primary' : ($keyword->current_position <= 20 ? 'warning' : 'secondary')) }}" style="font-size: 1em; min-width: 40px;">
                                        {{ $keyword->current_position }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($keyword->meta['clicks'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($keyword->meta['impressions'] ?? 0) }}</td>
                            <td class="text-right">{{ $keyword->meta['ctr'] ?? 0 }}%</td>
                            <td class="text-center">
                                <i class="fas fa-minus text-muted"></i>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <p class="mb-2">No keywords tracked yet</p>
                                @if($gscConnected)
                                    <button wire:click="syncFromGsc" class="btn btn-primary">
                                        <i class="fab fa-google mr-1"></i> Sync from Search Console
                                    </button>
                                @else
                                    <p class="text-muted">Connect Google Search Console to import keywords</p>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($keywords->hasPages())
            <div class="card-footer">
                {{ $keywords->links() }}
            </div>
            @endif
        </div>
        @endif

        {{-- ==================== PAGE SPEED TAB ==================== --}}
        @if($activeTab === 'pagespeed')
        <div class="row mb-4">
            <div class="col-12 text-right">
                <button wire:click="runPageSpeedAnalysis" class="btn btn-lg btn-primary" wire:loading.attr="disabled">
                    <i class="fas fa-tachometer-alt mr-2"></i> Run Speed Analysis
                </button>
            </div>
        </div>

        @if(!empty($pageSpeedData) || !empty($pageSpeedDesktop))
        <div class="row">
            {{-- Mobile Scores --}}
            <div class="col-lg-6">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-mobile-alt mr-2"></i>Mobile</h3>
                    </div>
                    <div class="card-body">
                        @if(!empty($pageSpeedData['scores']))
                        <div class="row text-center">
                            @foreach(['performance' => 'Performance', 'accessibility' => 'Accessibility', 'best_practices' => 'Best Practices', 'seo' => 'SEO'] as $key => $label)
                            <div class="col-6 col-md-3 mb-3">
                                <div class="position-relative d-inline-block">
                                    <canvas id="mobile_{{ $key }}_gauge" width="80" height="80"></canvas>
                                    <div class="position-absolute w-100 text-center" style="top: 50%; left: 0; transform: translateY(-50%);">
                                        <strong>{{ $pageSpeedData['scores'][$key] ?? 0 }}</strong>
                                    </div>
                                </div>
                                <p class="mb-0 small">{{ $label }}</p>
                            </div>
                            @endforeach
                        </div>
                        <hr>
                        <h6><i class="fas fa-heartbeat mr-2"></i>Core Web Vitals</h6>
                        <div class="row">
                            @foreach($pageSpeedData['core_web_vitals'] ?? [] as $metric => $data)
                                @if(in_array($metric, ['lcp', 'fcp', 'cls', 'tbt']))
                                <div class="col-6 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ strtoupper($metric) }}</span>
                                        <strong>{{ $data['display'] ?? '—' }}</strong>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted text-center mb-0">Click "Run Speed Analysis" to test</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Desktop Scores --}}
            <div class="col-lg-6">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-desktop mr-2"></i>Desktop</h3>
                    </div>
                    <div class="card-body">
                        @if(!empty($pageSpeedDesktop['scores']))
                        <div class="row text-center">
                            @foreach(['performance' => 'Performance', 'accessibility' => 'Accessibility', 'best_practices' => 'Best Practices', 'seo' => 'SEO'] as $key => $label)
                            <div class="col-6 col-md-3 mb-3">
                                <div class="position-relative d-inline-block">
                                    <canvas id="desktop_{{ $key }}_gauge" width="80" height="80"></canvas>
                                    <div class="position-absolute w-100 text-center" style="top: 50%; left: 0; transform: translateY(-50%);">
                                        <strong>{{ $pageSpeedDesktop['scores'][$key] ?? 0 }}</strong>
                                    </div>
                                </div>
                                <p class="mb-0 small">{{ $label }}</p>
                            </div>
                            @endforeach
                        </div>
                        <hr>
                        <h6><i class="fas fa-heartbeat mr-2"></i>Core Web Vitals</h6>
                        <div class="row">
                            @foreach($pageSpeedDesktop['core_web_vitals'] ?? [] as $metric => $data)
                                @if(in_array($metric, ['lcp', 'fcp', 'cls', 'tbt']))
                                <div class="col-6 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ strtoupper($metric) }}</span>
                                        <strong>{{ $data['display'] ?? '—' }}</strong>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted text-center mb-0">Click "Run Speed Analysis" to test</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Opportunities --}}
        @if(!empty($pageSpeedData['opportunities']))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Opportunities for Improvement</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                        @foreach($pageSpeedData['opportunities'] as $opp)
                        <tr>
                            <td>
                                <i class="fas fa-arrow-right text-warning mr-2"></i>
                                <strong>{{ $opp['title'] }}</strong>
                                @if($opp['display'])
                                    <span class="badge badge-info ml-2">{{ $opp['display'] }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-tachometer-alt fa-4x text-muted mb-4"></i>
                <h4>Analyze Your Page Speed</h4>
                <p class="text-muted mb-4">Get detailed performance metrics for mobile and desktop using Google PageSpeed Insights.</p>
                <button wire:click="runPageSpeedAnalysis" class="btn btn-lg btn-primary">
                    <i class="fas fa-play mr-2"></i> Run Analysis
                </button>
            </div>
        </div>
        @endif
        @endif

        {{-- ==================== SECURITY TAB ==================== --}}
        @if($activeTab === 'security')
        <div class="row mb-4">
            <div class="col-12 text-right">
                <button wire:click="runSecurityScan" class="btn btn-lg btn-success" wire:loading.attr="disabled">
                    <i class="fas fa-shield-alt mr-2"></i> Run Security Scan
                </button>
            </div>
        </div>

        @if(!empty($securityData))
        <div class="row">
            <div class="col-lg-4">
                <div class="card bg-gradient-{{ $securityData['score'] >= 80 ? 'success' : ($securityData['score'] >= 50 ? 'warning' : 'danger') }}">
                    <div class="card-body text-center text-white">
                        <h6 class="text-uppercase">Security Score</h6>
                        <div class="display-1 font-weight-bold">{{ $securityData['score'] }}</div>
                        <p class="mb-0">{{ $securityData['passed'] }}/{{ $securityData['total'] }} checks passed</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-check-double mr-2"></i>Security Headers</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <tbody>
                                @foreach($securityData['checks'] as $check => $passed)
                                <tr>
                                    <td width="40">
                                        @if($passed)
                                            <span class="text-success"><i class="fas fa-check-circle fa-lg"></i></span>
                                        @else
                                            <span class="text-danger"><i class="fas fa-times-circle fa-lg"></i></span>
                                        @endif
                                    </td>
                                    <td>{{ str_replace('_', ' ', ucwords($check, '_')) }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-{{ $passed ? 'success' : 'danger' }}">
                                            {{ $passed ? 'Passed' : 'Failed' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($securityData['recommendations']))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Recommendations</h3>
            </div>
            <div class="card-body">
                @foreach($securityData['recommendations'] as $rec)
                <div class="callout callout-{{ $rec['severity'] === 'critical' ? 'danger' : ($rec['severity'] === 'high' ? 'warning' : 'info') }}">
                    <h5>
                        <span class="badge badge-{{ $rec['severity'] === 'critical' ? 'danger' : ($rec['severity'] === 'high' ? 'warning' : 'info') }} mr-2">
                            {{ ucfirst($rec['severity']) }}
                        </span>
                        {{ $rec['title'] }}
                    </h5>
                    <p class="mb-0">{{ $rec['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-shield-alt fa-4x text-muted mb-4"></i>
                <h4>Check Your Security Headers</h4>
                <p class="text-muted mb-4">Scan your website for important security headers that protect against common attacks.</p>
                <button wire:click="runSecurityScan" class="btn btn-lg btn-success">
                    <i class="fas fa-search mr-2"></i> Run Security Scan
                </button>
            </div>
        </div>
        @endif
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('livewire:load', function() {
    initCharts();

    Livewire.hook('message.processed', () => {
        setTimeout(initCharts, 100);
    });
});

function initCharts() {
    // Overall Score Gauge
    const overallGauge = document.getElementById('overallScoreGauge');
    if (overallGauge && !overallGauge.chart) {
        const score = {{ $overallScore }};
        overallGauge.chart = new Chart(overallGauge, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [
                        score >= 80 ? '#28a745' : (score >= 60 ? '#ffc107' : '#dc3545'),
                        '#e9ecef'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '75%',
                responsive: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
    }

    // Position Distribution Chart
    const posChart = document.getElementById('positionDistributionChart');
    if (posChart && !posChart.chart) {
        const distData = @json($positionDistribution);
        posChart.chart = new Chart(posChart, {
            type: 'doughnut',
            data: {
                labels: ['Top 3', 'Top 4-10', 'Top 11-20', 'Top 21-50', '50+'],
                datasets: [{
                    data: [distData['1-3'], distData['4-10'], distData['11-20'], distData['21-50'], distData['50+']],
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#fd7e14', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Performance Trend Chart
    const trendChart = document.getElementById('performanceTrendChart');
    if (trendChart && !trendChart.chart) {
        const trendData = @json($dailyTrendData);
        if (trendData.length > 0) {
            trendChart.chart = new Chart(trendChart, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.date),
                    datasets: [{
                        label: 'Clicks',
                        data: trendData.map(d => d.clicks),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0,123,255,0.1)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y'
                    }, {
                        label: 'Impressions',
                        data: trendData.map(d => d.impressions),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40,167,69,0.1)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { type: 'linear', position: 'left', title: { display: true, text: 'Clicks' } },
                        y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Impressions' } }
                    }
                }
            });
        }
    }

    // PageSpeed Gauges
    @if(!empty($pageSpeedData['scores']))
    @foreach(['performance', 'accessibility', 'best_practices', 'seo'] as $metric)
    createScoreGauge('mobile_{{ $metric }}_gauge', {{ $pageSpeedData['scores'][$metric] ?? 0 }});
    @endforeach
    @endif

    @if(!empty($pageSpeedDesktop['scores']))
    @foreach(['performance', 'accessibility', 'best_practices', 'seo'] as $metric)
    createScoreGauge('desktop_{{ $metric }}_gauge', {{ $pageSpeedDesktop['scores'][$metric] ?? 0 }});
    @endforeach
    @endif
}

function createScoreGauge(id, score) {
    const el = document.getElementById(id);
    if (el && !el.chart) {
        el.chart = new Chart(el, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [
                        score >= 90 ? '#28a745' : (score >= 50 ? '#ffc107' : '#dc3545'),
                        '#e9ecef'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
    }
}
</script>
@endpush

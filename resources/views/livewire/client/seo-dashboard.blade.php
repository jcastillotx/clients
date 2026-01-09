<div>
    {{-- Page Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-search-dollar mr-2"></i>SEO Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <div class="float-sm-right">
                        <div class="input-group" style="max-width: 400px;">
                            <input type="url" wire:model.defer="websiteUrl" class="form-control" placeholder="Enter website URL...">
                            <div class="input-group-append">
                                <button wire:click="runPageSpeedAnalysis" class="btn btn-primary" wire:loading.attr="disabled">
                                    <i class="fas fa-sync-alt" wire:loading.class="fa-spin" wire:target="runPageSpeedAnalysis"></i>
                                    Analyze
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="container-fluid">
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a wire:click.prevent="setTab('overview')" class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" href="#">
                            <i class="fas fa-chart-pie mr-1"></i> Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a wire:click.prevent="setTab('keywords')" class="nav-link {{ $activeTab === 'keywords' ? 'active' : '' }}" href="#">
                            <i class="fas fa-key mr-1"></i> Keyword Rankings
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
                    <li class="nav-item">
                        <a wire:click.prevent="setTab('engines')" class="nav-link {{ $activeTab === 'engines' ? 'active' : '' }}" href="#">
                            <i class="fas fa-globe mr-1"></i> Search Engines
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                {{-- Overview Tab --}}
                @if($activeTab === 'overview')
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $overviewStats['overall_score'] }}<sup style="font-size: 20px">%</sup></h3>
                                <p>Overall SEO Score</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $overviewStats['keywords_tracked'] }}</h3>
                                <p>Keywords Tracked</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-key"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $overviewStats['avg_position'] }}</h3>
                                <p>Avg. Position</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-sort-numeric-up"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>{{ $overviewStats['top_10_keywords'] }}</h3>
                                <p>Top 10 Keywords</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-bolt mr-1"></i> Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <button wire:click="runPageSpeedAnalysis" class="btn btn-outline-primary btn-block" wire:loading.attr="disabled">
                                            <i class="fas fa-tachometer-alt mr-1"></i> Run Speed Test
                                        </button>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <button wire:click="runSecurityScan" class="btn btn-outline-success btn-block" wire:loading.attr="disabled">
                                            <i class="fas fa-shield-alt mr-1"></i> Security Scan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> SEO Health Summary</h3>
                            </div>
                            <div class="card-body">
                                @if($keywordStats['total'] > 0)
                                    <p class="mb-2">
                                        <strong>{{ $keywordStats['top_10_percentage'] }}%</strong> of your keywords are in the top 10
                                    </p>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" style="width: {{ $keywordStats['top_10_percentage'] }}%"></div>
                                    </div>
                                    <small class="text-muted">
                                        {{ $keywordStats['top_3'] }} keywords in top 3 |
                                        {{ $keywordStats['top_10'] }} keywords in top 10
                                    </small>
                                @else
                                    <p class="text-muted mb-0">No keywords being tracked yet. Add keywords to start monitoring your rankings.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Keywords Tab --}}
                @if($activeTab === 'keywords')
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-key"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Keywords</span>
                                <span class="info-box-number">{{ $keywordStats['total'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-medal"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Top 3 Positions</span>
                                <span class="info-box-number">{{ $keywordStats['top_3'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-warning">
                            <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Top 10 Positions</span>
                                <span class="info-box-number">{{ $keywordStats['top_10'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-primary">
                            <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Avg. Position</span>
                                <span class="info-box-number">{{ $keywordStats['avg_position'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list mr-1"></i> Tracked Keywords</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Current Position</th>
                                    <th>Target</th>
                                    <th>Search Volume</th>
                                    <th>Difficulty</th>
                                    <th>CPC</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($keywords as $keyword)
                                <tr>
                                    <td>
                                        <strong>{{ $keyword->keyword }}</strong>
                                        <br><small class="text-muted">{{ $keyword->website_url }}</small>
                                    </td>
                                    <td>
                                        @if($keyword->current_position)
                                            <span class="badge badge-{{ $keyword->current_position <= 3 ? 'success' : ($keyword->current_position <= 10 ? 'warning' : 'secondary') }}">
                                                #{{ $keyword->current_position }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">Not ranked</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($keyword->target_position)
                                            #{{ $keyword->target_position }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ number_format($keyword->search_volume ?? 0) }}</td>
                                    <td>
                                        @if($keyword->difficulty)
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-{{ $keyword->difficulty <= 30 ? 'success' : ($keyword->difficulty <= 60 ? 'warning' : 'danger') }}"
                                                     style="width: {{ $keyword->difficulty }}%"></div>
                                            </div>
                                            <small>{{ $keyword->difficulty }}%</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>${{ number_format($keyword->cpc ?? 0, 2) }}</td>
                                    <td>
                                        @if($keyword->current_position && $keyword->target_position)
                                            @if($keyword->current_position <= $keyword->target_position)
                                                <span class="text-success"><i class="fas fa-check-circle"></i> On Target</span>
                                            @else
                                                <span class="text-warning"><i class="fas fa-arrow-up"></i> Improving</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-search fa-2x mb-2"></i>
                                        <p>No keywords being tracked. Contact your account manager to set up keyword tracking.</p>
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

                {{-- Page Speed Tab --}}
                @if($activeTab === 'pagespeed')
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <button type="button" wire:click="$set('selectedDevice', 'mobile')"
                                    class="btn {{ $selectedDevice === 'mobile' ? 'btn-primary' : 'btn-outline-primary' }}">
                                <i class="fas fa-mobile-alt mr-1"></i> Mobile
                            </button>
                            <button type="button" wire:click="$set('selectedDevice', 'desktop')"
                                    class="btn {{ $selectedDevice === 'desktop' ? 'btn-primary' : 'btn-outline-primary' }}">
                                <i class="fas fa-desktop mr-1"></i> Desktop
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <button wire:click="runPageSpeedAnalysis" class="btn btn-success" wire:loading.attr="disabled">
                            <i class="fas fa-sync-alt" wire:loading.class="fa-spin" wire:target="runPageSpeedAnalysis"></i>
                            Run Analysis
                        </button>
                    </div>
                </div>

                @if(!empty($pageSpeedData))
                {{-- Core Web Vitals Scores --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="card {{ $pageSpeedData['performance'] >= 90 ? 'card-success' : ($pageSpeedData['performance'] >= 50 ? 'card-warning' : 'card-danger') }}">
                            <div class="card-body text-center">
                                <h1 class="display-4">{{ $pageSpeedData['performance'] }}</h1>
                                <p class="mb-0">Performance</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card {{ $pageSpeedData['accessibility'] >= 90 ? 'card-success' : ($pageSpeedData['accessibility'] >= 50 ? 'card-warning' : 'card-danger') }}">
                            <div class="card-body text-center">
                                <h1 class="display-4">{{ $pageSpeedData['accessibility'] }}</h1>
                                <p class="mb-0">Accessibility</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card {{ $pageSpeedData['best_practices'] >= 90 ? 'card-success' : ($pageSpeedData['best_practices'] >= 50 ? 'card-warning' : 'card-danger') }}">
                            <div class="card-body text-center">
                                <h1 class="display-4">{{ $pageSpeedData['best_practices'] }}</h1>
                                <p class="mb-0">Best Practices</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card {{ $pageSpeedData['seo'] >= 90 ? 'card-success' : ($pageSpeedData['seo'] >= 50 ? 'card-warning' : 'card-danger') }}">
                            <div class="card-body text-center">
                                <h1 class="display-4">{{ $pageSpeedData['seo'] }}</h1>
                                <p class="mb-0">SEO</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Core Web Vitals Metrics --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-heartbeat mr-1"></i> Core Web Vitals</h3>
                        <div class="card-tools">
                            <span class="badge badge-info">{{ ucfirst($pageSpeedData['device']) }}</span>
                            @if(!empty($pageSpeedData['is_mock']))
                                <span class="badge badge-warning">Sample Data</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-paint-brush"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">First Contentful Paint (FCP)</span>
                                        <span class="info-box-number">{{ $pageSpeedData['fcp'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-image"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Largest Contentful Paint (LCP)</span>
                                        <span class="info-box-number">{{ $pageSpeedData['lcp'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-arrows-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Cumulative Layout Shift (CLS)</span>
                                        <span class="info-box-number">{{ $pageSpeedData['cls'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-hand-paper"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Blocking Time (TBT)</span>
                                        <span class="info-box-number">{{ $pageSpeedData['tbt'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-tachometer-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Speed Index</span>
                                        <span class="info-box-number">{{ $pageSpeedData['speed_index'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-secondary"><i class="fas fa-mouse-pointer"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Time to Interactive (TTI)</span>
                                        <span class="info-box-number">{{ $pageSpeedData['tti'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small><i class="fas fa-clock mr-1"></i> Last analyzed: {{ $pageSpeedData['fetched_at'] ?? 'N/A' }}</small>
                    </div>
                </div>
                @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tachometer-alt fa-3x text-muted mb-3"></i>
                        <h4>No Page Speed Data</h4>
                        <p class="text-muted">Enter your website URL and click "Run Analysis" to see page speed metrics.</p>
                        <button wire:click="runPageSpeedAnalysis" class="btn btn-primary" wire:loading.attr="disabled">
                            <i class="fas fa-play mr-1"></i> Run Page Speed Analysis
                        </button>
                    </div>
                </div>
                @endif
                @endif

                {{-- Security Tab --}}
                @if($activeTab === 'security')
                <div class="row mb-3">
                    <div class="col-12 text-right">
                        <button wire:click="runSecurityScan" class="btn btn-success" wire:loading.attr="disabled">
                            <i class="fas fa-shield-alt" wire:loading.class="fa-spin" wire:target="runSecurityScan"></i>
                            Run Security Scan
                        </button>
                    </div>
                </div>

                @if(!empty($securityData))
                <div class="row">
                    <div class="col-md-4">
                        <div class="card {{ $securityData['score'] >= 80 ? 'card-success' : ($securityData['score'] >= 50 ? 'card-warning' : 'card-danger') }}">
                            <div class="card-body text-center">
                                <h1 class="display-3">{{ $securityData['score'] }}</h1>
                                <p class="lead mb-0">Security Score</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-check-double mr-1"></i> Security Checks</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <tbody>
                                        @foreach($securityData['checks'] as $check => $passed)
                                        <tr>
                                            <td>
                                                @if($passed)
                                                    <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                                @else
                                                    <span class="text-danger"><i class="fas fa-times-circle"></i></span>
                                                @endif
                                            </td>
                                            <td>{{ str_replace('_', ' ', ucfirst($check)) }}</td>
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
                        <h3 class="card-title"><i class="fas fa-lightbulb mr-1"></i> Security Recommendations</h3>
                    </div>
                    <div class="card-body">
                        @foreach($securityData['recommendations'] as $rec)
                        <div class="callout callout-{{ $rec['severity'] === 'critical' ? 'danger' : ($rec['severity'] === 'high' ? 'warning' : ($rec['severity'] === 'medium' ? 'info' : 'secondary')) }}">
                            <h5>
                                <span class="badge badge-{{ $rec['severity'] === 'critical' ? 'danger' : ($rec['severity'] === 'high' ? 'warning' : ($rec['severity'] === 'medium' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($rec['severity']) }}
                                </span>
                                {{ $rec['title'] }}
                            </h5>
                            <p class="mb-0">{{ $rec['description'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    <div class="card-footer text-muted">
                        <small><i class="fas fa-clock mr-1"></i> Last scanned: {{ $securityData['fetched_at'] ?? 'N/A' }}</small>
                    </div>
                </div>
                @endif
                @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                        <h4>No Security Data</h4>
                        <p class="text-muted">Click "Run Security Scan" to analyze your website's security headers.</p>
                        <button wire:click="runSecurityScan" class="btn btn-primary" wire:loading.attr="disabled">
                            <i class="fas fa-play mr-1"></i> Run Security Scan
                        </button>
                    </div>
                </div>
                @endif
                @endif

                {{-- Search Engines Tab --}}
                @if($activeTab === 'engines')
                <div class="row">
                    @foreach($searchEngineData as $engine => $data)
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="{{ $data['icon'] }} mr-1"></i> {{ $data['name'] }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 text-center border-right">
                                        <h3 class="mb-0">{{ round($data['avg_position'], 1) ?: '—' }}</h3>
                                        <small class="text-muted">Avg Position</small>
                                    </div>
                                    <div class="col-6 text-center">
                                        <h3 class="mb-0">{{ $data['keywords_tracked'] }}</h3>
                                        <small class="text-muted">Keywords</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <span class="badge badge-success">{{ $data['top_10'] }} in Top 10</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> About Search Engine Rankings</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">
                            Rankings are tracked across multiple search engines to give you a comprehensive view of your SEO performance.
                            Each search engine uses different algorithms, so rankings may vary. Focus on Google for primary SEO efforts,
                            but don't ignore Bing which powers Yahoo and DuckDuckGo results.
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

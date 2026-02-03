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
    <x-page-header
        heading="Manage Connections"
        subheading="Connect your accounts to unlock powerful marketing and SEO features."
        icon="fas fa-plug"
    />

    <div class="container-fluid">
        {{-- Navigation Tabs --}}
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <a wire:click.prevent="setTab('overview')" class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" href="#">
                    <i class="fas fa-th-large mr-1"></i> Overview
                </a>
            </li>
            <li class="nav-item">
                <a wire:click.prevent="setTab('seo')" class="nav-link {{ $activeTab === 'seo' ? 'active' : '' }}" href="#">
                    <i class="fab fa-google mr-1"></i> Google Search Console
                </a>
            </li>
            <li class="nav-item">
                <a wire:click.prevent="setTab('social')" class="nav-link {{ $activeTab === 'social' ? 'active' : '' }}" href="#">
                    <i class="fas fa-share-alt mr-1"></i> Social Media
                </a>
            </li>
            <li class="nav-item">
                <a wire:click.prevent="setTab('analytics')" class="nav-link {{ $activeTab === 'analytics' ? 'active' : '' }}" href="#">
                    <i class="fas fa-chart-bar mr-1"></i> Analytics
                </a>
            </li>
        </ul>

        {{-- ==================== OVERVIEW TAB ==================== --}}
        @if($activeTab === 'overview')
        <div class="row">
            {{-- Google Search Console Card --}}
            <div class="col-lg-4 mb-4">
                <div class="card h-100 {{ $gscConnected ? 'card-outline card-success' : '' }}">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fab fa-google mr-2" style="color: #4285F4;"></i>
                            Search Console
                            @if($gscConnected)
                                <span class="badge badge-success ml-2">Connected</span>
                            @endif
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Track keyword rankings, clicks, and search performance.</p>

                        @if($gscConnected)
                            <div class="alert alert-success py-2 mb-3">
                                <small><i class="fas fa-check-circle mr-1"></i> {{ $gscEmail }}</small>
                            </div>
                            <a href="{{ route('client.seo') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-chart-line mr-1"></i> View SEO Dashboard
                            </a>
                        @else
                            <a href="{{ route('oauth.gsc.redirect') }}" class="btn btn-primary btn-block">
                                <i class="fab fa-google mr-1"></i> Connect
                            </a>
                        @endif
                    </div>
                    @if(!$gscConnected)
                    <div class="card-footer bg-light">
                        <a wire:click.prevent="setTab('seo')" href="#" class="text-primary small">
                            <i class="fas fa-question-circle mr-1"></i> Setup guide
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Social Media Card --}}
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-share-alt mr-2 text-primary"></i>
                            Social Media
                            @php $connectedCount = count($socialAccounts); @endphp
                            @if($connectedCount > 0)
                                <span class="badge badge-success ml-2">{{ $connectedCount }}</span>
                            @endif
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Schedule posts and track engagement across platforms.</p>

                        <div class="d-flex justify-content-around mb-3">
                            @foreach(['facebook', 'instagram', 'linkedin', 'twitter'] as $platform)
                                @php $isConnected = isset($socialAccounts[$platform]); @endphp
                                <div class="text-center">
                                    <div class="position-relative d-inline-block">
                                        <i class="{{ $socialPlatforms[$platform]['icon'] }}" style="font-size: 1.5rem; color: {{ $socialPlatforms[$platform]['color'] }};"></i>
                                        @if($isConnected)
                                            <i class="fas fa-check-circle text-success position-absolute" style="font-size: 0.7rem; bottom: -3px; right: -3px;"></i>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <a wire:click.prevent="setTab('social')" href="#" class="btn btn-outline-primary btn-block btn-sm">
                            <i class="fas fa-cog mr-1"></i> Manage Accounts
                        </a>
                    </div>
                </div>
            </div>

            {{-- Analytics Card --}}
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar mr-2 text-success"></i>
                            Analytics
                            @if(count($analyticsAccounts) > 0)
                                <span class="badge badge-success ml-2">{{ count($analyticsAccounts) }}</span>
                            @endif
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Connect Google Analytics for traffic insights.</p>

                        @if(count($analyticsAccounts) > 0)
                            @foreach($analyticsAccounts as $account)
                            <div class="alert alert-success py-2 mb-2">
                                <small><i class="fas fa-check-circle mr-1"></i> {{ $account->account_name ?? $account->account_email }}</small>
                            </div>
                            @endforeach
                        @else
                            <a href="{{ route('oauth.analytics.google.redirect') }}" class="btn btn-success btn-block">
                                <i class="fab fa-google mr-1"></i> Connect Analytics
                            </a>
                        @endif
                    </div>
                    <div class="card-footer bg-light">
                        <a wire:click.prevent="setTab('analytics')" href="#" class="text-primary small">
                            <i class="fas fa-cog mr-1"></i> Manage
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Benefits Section --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-star mr-2"></i>Why Connect Your Accounts?</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-search-dollar fa-2x text-primary mb-2"></i>
                        <h6>SEO Insights</h6>
                        <p class="text-muted small mb-0">Real keyword rankings from Google</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-calendar-alt fa-2x text-success mb-2"></i>
                        <h6>Auto-Publishing</h6>
                        <p class="text-muted small mb-0">Schedule posts automatically</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h6>Performance Data</h6>
                        <p class="text-muted small mb-0">Track engagement and traffic</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-shield-alt fa-2x text-warning mb-2"></i>
                        <h6>Secure & Encrypted</h6>
                        <p class="text-muted small mb-0">Your data is always safe</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ==================== SEO / GSC TAB ==================== --}}
        @if($activeTab === 'seo')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fab fa-google mr-2"></i> Google Search Console
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($gscConnected)
                            <div class="alert alert-success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-check-circle fa-lg mr-2"></i>
                                        <strong>Connected!</strong>
                                        <span class="ml-2">{{ $gscEmail }}</span>
                                    </div>
                                    <form action="{{ route('oauth.gsc.disconnect') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Disconnect Google Search Console?')">
                                            <i class="fas fa-unlink mr-1"></i> Disconnect
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if(count($gscSites) > 0)
                            <div class="card card-outline card-primary">
                                <div class="card-header py-2">
                                    <h5 class="card-title mb-0">Select Website to Track</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('oauth.gsc.update-site') }}" method="POST">
                                        @csrf
                                        <div class="input-group">
                                            <select name="site_url" class="form-control">
                                                <option value="">-- Select website --</option>
                                                @foreach($gscSites as $site)
                                                    <option value="{{ $site }}" {{ $client->gsc_site_url === $site ? 'selected' : '' }}>
                                                        {{ $site }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                    <form action="{{ route('oauth.gsc.refresh-sites') }}" method="POST" class="mt-2">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-sync-alt mr-1"></i> Refresh site list
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>No websites found.</strong> Add your website to Google Search Console first.
                            </div>
                            @endif

                            <a href="{{ route('client.seo') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-chart-line mr-1"></i> Go to SEO Dashboard
                            </a>
                        @else
                            <div class="text-center py-4">
                                <i class="fab fa-google fa-4x mb-3" style="color: #4285F4;"></i>
                                <h4>Connect Google Search Console</h4>
                                <p class="text-muted">Get real ranking data, clicks, and impressions directly from Google.</p>
                                <a href="{{ route('oauth.gsc.redirect') }}" class="btn btn-lg btn-primary">
                                    <i class="fab fa-google mr-2"></i> Connect Now
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book mr-2"></i>Setup Guide</h3>
                    </div>
                    <div class="card-body">
                        <div class="step mb-3">
                            <span class="badge badge-info mr-2">1</span>
                            <strong>Add Website to GSC</strong>
                            <p class="text-muted small mb-0 ml-4">Go to <a href="https://search.google.com/search-console" target="_blank">Search Console</a> and add your website.</p>
                        </div>
                        <div class="step mb-3">
                            <span class="badge badge-info mr-2">2</span>
                            <strong>Verify Ownership</strong>
                            <p class="text-muted small mb-0 ml-4">Use HTML file, meta tag, or Google Analytics verification.</p>
                        </div>
                        <div class="step mb-3">
                            <span class="badge badge-info mr-2">3</span>
                            <strong>Connect Here</strong>
                            <p class="text-muted small mb-0 ml-4">Click connect, sign in with Google, and grant access.</p>
                        </div>
                        <div class="step">
                            <span class="badge badge-success mr-2"><i class="fas fa-check"></i></span>
                            <strong>Done!</strong>
                            <p class="text-muted small mb-0 ml-4">View rankings in your SEO Dashboard.</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <p class="small text-muted mb-2">Need help setting up?</p>
                        <a href="{{ route('client.messaging') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-comments mr-1"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ==================== SOCIAL MEDIA TAB ==================== --}}
        @if($activeTab === 'social')
        <div class="row">
            @foreach($socialPlatforms as $platform => $info)
                @php $isConnected = isset($socialAccounts[$platform]); @endphp
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 {{ $isConnected ? 'card-outline card-success' : '' }}">
                        <div class="card-header" style="border-left: 4px solid {{ $info['color'] }};">
                            <h3 class="card-title">
                                <i class="{{ $info['icon'] }} mr-2" style="color: {{ $info['color'] }};"></i>
                                {{ $info['name'] }}
                                @if($isConnected)
                                    <span class="badge badge-success ml-2">Connected</span>
                                @endif
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">{{ $info['description'] }}</p>

                            <ul class="list-unstyled small mb-3">
                                @foreach($info['features'] as $feature)
                                    <li><i class="fas fa-check text-success mr-1"></i> {{ $feature }}</li>
                                @endforeach
                            </ul>

                            @if($isConnected)
                                @php $account = $socialAccounts[$platform]; @endphp
                                <div class="alert alert-success py-2 mb-2">
                                    <small><i class="fas fa-check-circle mr-1"></i> {{ $account->account_name ?? 'Connected' }}</small>
                                </div>
                                <form action="{{ route('oauth.disconnect', $platform) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Disconnect {{ $info['name'] }}?')">
                                        <i class="fas fa-unlink mr-1"></i> Disconnect
                                    </button>
                                </form>
                            @else
                                @if($platform === 'instagram')
                                    <a href="{{ route('oauth.facebook.redirect') }}" class="btn btn-block" style="background-color: {{ $info['color'] }}; color: white;">
                                        <i class="{{ $info['icon'] }} mr-1"></i> Connect via Facebook
                                    </a>
                                    <small class="text-muted d-block mt-1">Instagram connects through Facebook.</small>
                                @else
                                    <a href="{{ route('oauth.' . $platform . '.redirect') }}" class="btn btn-block" style="background-color: {{ $info['color'] }}; color: white;">
                                        <i class="{{ $info['icon'] }} mr-1"></i> Connect
                                    </a>
                                @endif
                            @endif
                        </div>
                        <div class="card-footer bg-light">
                            <a wire:click.prevent="showSocialSetup('{{ $platform }}')" href="#" class="text-primary small">
                                <i class="fas fa-question-circle mr-1"></i> Setup guide
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Social Setup Guide Modal --}}
        @if($showSocialGuide && $selectedSocialPlatform && isset($socialPlatforms[$selectedSocialPlatform]))
            @php $platformInfo = $socialPlatforms[$selectedSocialPlatform]; @endphp
            <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header" style="border-left: 4px solid {{ $platformInfo['color'] }};">
                            <h5 class="modal-title">
                                <i class="{{ $platformInfo['icon'] }} mr-2" style="color: {{ $platformInfo['color'] }};"></i>
                                {{ $platformInfo['name'] }} Setup Guide
                            </h5>
                            <button wire:click="closeSocialGuide" type="button" class="close">&times;</button>
                        </div>
                        <div class="modal-body">
                            @switch($selectedSocialPlatform)
                                @case('facebook')
                                    <h6>Requirements:</h6>
                                    <ul><li>A Facebook Business Page with admin access</li></ul>
                                    <h6>Steps:</h6>
                                    <ol>
                                        <li>Click "Connect" button</li>
                                        <li>Log in to Facebook</li>
                                        <li>Select the Pages to connect</li>
                                        <li>Grant required permissions</li>
                                    </ol>
                                    @break
                                @case('instagram')
                                    <h6>Requirements:</h6>
                                    <ul>
                                        <li>Instagram Business or Creator account</li>
                                        <li>Instagram linked to a Facebook Page</li>
                                    </ul>
                                    <h6>Steps:</h6>
                                    <ol>
                                        <li>Link Instagram to Facebook in Instagram settings first</li>
                                        <li>Click "Connect via Facebook"</li>
                                        <li>Select your Facebook Page</li>
                                        <li>Allow Instagram permissions</li>
                                    </ol>
                                    <div class="alert alert-info mt-2">
                                        <i class="fas fa-info-circle mr-1"></i> Personal accounts must convert to Business/Creator first.
                                    </div>
                                    @break
                                @case('linkedin')
                                    <h6>Requirements:</h6>
                                    <ul><li>LinkedIn Company Page with admin access</li></ul>
                                    <h6>Steps:</h6>
                                    <ol>
                                        <li>Click "Connect"</li>
                                        <li>Sign in to LinkedIn</li>
                                        <li>Select your Company Page</li>
                                    </ol>
                                    @break
                                @case('twitter')
                                    <h6>Requirements:</h6>
                                    <ul><li>X (Twitter) account with verified email</li></ul>
                                    <h6>Steps:</h6>
                                    <ol>
                                        <li>Click "Connect"</li>
                                        <li>Log in to X</li>
                                        <li>Authorize the app</li>
                                    </ol>
                                    @break
                                @default
                                    <p>Click Connect and follow the on-screen instructions.</p>
                            @endswitch
                        </div>
                        <div class="modal-footer">
                            <button wire:click="closeSocialGuide" type="button" class="btn btn-secondary">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @endif

        {{-- ==================== ANALYTICS TAB ==================== --}}
        @if($activeTab === 'analytics')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fab fa-google mr-2" style="color: #E37400;"></i> Google Analytics</h3>
                    </div>
                    <div class="card-body">
                        @if(count($analyticsAccounts) > 0)
                            @foreach($analyticsAccounts as $account)
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-2">
                                <div>
                                    <strong>{{ $account->account_name ?? 'Google Analytics' }}</strong>
                                    <br><small class="text-muted">{{ $account->account_email }}</small>
                                </div>
                                <span class="badge badge-success">Connected</span>
                            </div>
                            @endforeach
                            <a href="{{ route('analytics.accounts') }}" class="btn btn-outline-primary mt-3">
                                <i class="fas fa-cog mr-1"></i> Manage Analytics Accounts
                            </a>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-bar fa-4x mb-3 text-muted"></i>
                                <h4>Connect Google Analytics</h4>
                                <p class="text-muted">Import website traffic data and visitor insights.</p>
                                <a href="{{ route('oauth.analytics.google.redirect') }}" class="btn btn-lg btn-success">
                                    <i class="fab fa-google mr-2"></i> Connect Analytics
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book mr-2"></i>Setup Guide</h3>
                    </div>
                    <div class="card-body">
                        <div class="step mb-3">
                            <span class="badge badge-info mr-2">1</span>
                            <strong>Click Connect</strong>
                        </div>
                        <div class="step mb-3">
                            <span class="badge badge-info mr-2">2</span>
                            <strong>Sign in with Google</strong>
                        </div>
                        <div class="step mb-3">
                            <span class="badge badge-info mr-2">3</span>
                            <strong>Select your Analytics property</strong>
                        </div>
                        <div class="step">
                            <span class="badge badge-success mr-2"><i class="fas fa-check"></i></span>
                            <strong>Done!</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

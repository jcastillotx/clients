<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plug mr-2"></i>
                        Account Connections
                    </h3>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Connect your social media and analytics accounts to enable automated posting and comprehensive reporting. All credentials are encrypted and stored securely.
                    </div>

                    <!-- Social Media Accounts Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">
                                <i class="fas fa-share-alt mr-2"></i>
                                Social Media Accounts
                            </h4>
                            <a href="{{ route('social.accounts') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-cog mr-1"></i>
                                Manage Social Accounts
                            </a>
                        </div>

                        @if($socialAccounts->count() > 0)
                            <div class="row">
                                @foreach($socialAccounts as $account)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-left-primary">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center"
                                                         style="width: 40px; height: 40px; background-color: {{ $account->platform_color }}; color: white;">
                                                        <i class="{{ $account->platform_icon }} fa-lg"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0">{{ ucfirst($account->platform) }}</h6>
                                                        <small class="text-muted">{{ $account->account_name }}</small>
                                                    </div>
                                                    <span class="badge {{ $account->status_badge_class }}">
                                                        {{ $account->status_text }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-light">
                                <i class="fas fa-info-circle mr-2"></i>
                                No social media accounts connected yet.
                                <a href="{{ route('social.accounts') }}" class="alert-link">Connect your first account</a>
                            </div>
                        @endif
                    </div>

                    <hr>

                    <!-- Analytics Accounts Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Analytics Accounts
                            </h4>
                            <a href="{{ route('analytics.accounts') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-cog mr-1"></i>
                                Manage Analytics Accounts
                            </a>
                        </div>

                        @if($analyticsAccounts->count() > 0)
                            <div class="row">
                                @foreach($analyticsAccounts as $account)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-left-success">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center"
                                                         style="width: 40px; height: 40px; background-color: {{ $account->platform_color }}; color: white;">
                                                        <i class="{{ $account->platform_icon }} fa-lg"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0">{{ $account->platform_display_name }}</h6>
                                                        <small class="text-muted">{{ $account->property_name ?? $account->account_email }}</small>
                                                    </div>
                                                    <span class="badge {{ $account->status_badge_class }}">
                                                        {{ $account->status_text }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-light">
                                <i class="fas fa-info-circle mr-2"></i>
                                No analytics accounts connected yet.
                                <a href="{{ route('analytics.accounts') }}" class="alert-link">Connect your first account</a>
                            </div>
                        @endif
                    </div>

                    <hr>

                    <!-- Quick Stats -->
                    <div class="row text-center">
                        <div class="col-md-6">
                            <div class="border-right">
                                <h3 class="text-primary">{{ $socialAccounts->count() }}</h3>
                                <p class="text-muted mb-0">Social Media Connections</p>
                                <small class="text-muted">
                                    {{ $socialAccounts->where('is_connected', true)->count() }} active
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h3 class="text-success">{{ $analyticsAccounts->count() }}</h3>
                            <p class="text-muted mb-0">Analytics Connections</p>
                            <small class="text-muted">
                                {{ $analyticsAccounts->where('is_connected', true)->count() }} active
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Benefits Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star mr-2"></i>
                        Why Connect Your Accounts?
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-rocket fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6>Automated Social Media Posting</h6>
                                    <p class="text-muted mb-0">
                                        Connect your social media accounts so we can automatically publish approved content on your behalf, saving you time.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-chart-line fa-2x text-success"></i>
                                </div>
                                <div>
                                    <h6>Comprehensive Analytics</h6>
                                    <p class="text-muted mb-0">
                                        Link your analytics platforms to give us access to your data for better insights and reporting.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-shield-alt fa-2x text-info"></i>
                                </div>
                                <div>
                                    <h6>Secure & Encrypted</h6>
                                    <p class="text-muted mb-0">
                                        All credentials are encrypted using industry-standard encryption. We never store your passwords.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                                <div>
                                    <h6>Save Time</h6>
                                    <p class="text-muted mb-0">
                                        No more manual posting or sharing reports. We handle everything for you automatically.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-share-alt mr-2"></i>
                        Connected Social Media Accounts
                    </h3>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Connect your social media accounts to enable automated post publishing. Your credentials are encrypted and stored securely.
                    </div>

                    <!-- Connected Accounts -->
                    @if(count($accounts) > 0)
                        <h5 class="mb-3">Your Connected Accounts</h5>
                        <div class="row mb-4">
                            @foreach($accounts as $account)
                                <div class="col-md-6 mb-3">
                                    <div class="card border-left-primary">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="d-flex align-items-center">
                                                    @if($account['profile_picture_url'])
                                                        <img src="{{ $account['profile_picture_url'] }}"
                                                             alt="{{ $account['account_name'] }}"
                                                             class="rounded-circle mr-3"
                                                             style="width: 50px; height: 50px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center"
                                                             style="width: 50px; height: 50px; background-color: {{ $account['platform_color'] }}; color: white;">
                                                            <i class="{{ $account['platform_icon'] }} fa-lg"></i>
                                                        </div>
                                                    @endif

                                                    <div>
                                                        <h5 class="mb-0">
                                                            <i class="{{ $account['platform_icon'] }} mr-1"
                                                               style="color: {{ $account['platform_color'] }}"></i>
                                                            {{ ucfirst($account['platform']) }}
                                                        </h5>
                                                        <p class="mb-0 text-muted">{{ $account['account_name'] }}</p>
                                                        @if($account['account_email'])
                                                            <small class="text-muted">{{ $account['account_email'] }}</small>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="text-right">
                                                    <span class="badge {{ $account['status_badge_class'] }} mb-2">
                                                        {{ $account['status_text'] }}
                                                    </span>
                                                    <br>
                                                    @if($account['connected_at'])
                                                        <small class="text-muted">
                                                            Connected {{ \Carbon\Carbon::parse($account['connected_at'])->diffForHumans() }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                @if($account['token_expires_at'])
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        Token expires {{ \Carbon\Carbon::parse($account['token_expires_at'])->diffForHumans() }}
                                                    </small>
                                                @endif

                                                @if($account['last_post_at'])
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-paper-plane mr-1"></i>
                                                        Last post {{ \Carbon\Carbon::parse($account['last_post_at'])->diffForHumans() }}
                                                    </small>
                                                @endif
                                            </div>

                                            <div class="mt-3 d-flex justify-content-between">
                                                <div>
                                                    @if($account['is_connected'] && isset($account['token_expires_at']))
                                                        <button wire:click="refreshAccount({{ $account['id'] }})"
                                                                class="btn btn-sm btn-outline-primary"
                                                                wire:loading.attr="disabled"
                                                                wire:target="refreshAccount({{ $account['id'] }})">
                                                            <i class="fas fa-sync-alt mr-1"
                                                               wire:loading.class="fa-spin"
                                                               wire:target="refreshAccount({{ $account['id'] }})"></i>
                                                            Refresh Token
                                                        </button>
                                                    @endif
                                                </div>

                                                <form action="{{ route('oauth.disconnect', $account['platform']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to disconnect this account?')">
                                                        <i class="fas fa-unlink mr-1"></i>
                                                        Disconnect
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr>
                    @endif

                    <!-- Available Platforms -->
                    <h5 class="mb-3">{{ count($accounts) > 0 ? 'Connect More Accounts' : 'Connect Your First Account' }}</h5>
                    <div class="row">
                        @foreach($availablePlatforms as $platform => $details)
                            @php
                                $isConnected = collect($accounts)->where('platform', $platform)->first();
                            @endphp

                            @if(!$isConnected)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 {{ $details['disabled'] ?? false ? 'bg-light' : '' }}">
                                        <div class="card-body text-center">
                                            <div class="mb-3" style="color: {{ $details['color'] }}">
                                                <i class="{{ $details['icon'] }} fa-3x"></i>
                                            </div>
                                            <h5 class="card-title">{{ $details['name'] }}</h5>
                                            <p class="card-text text-muted">{{ $details['description'] }}</p>

                                            @if($details['disabled'] ?? false)
                                                <span class="badge badge-warning">Coming Soon</span>
                                            @else
                                                <a href="{{ route('oauth.' . $platform . '.redirect') }}"
                                                   class="btn btn-primary btn-block">
                                                    <i class="fas fa-link mr-1"></i>
                                                    Connect {{ $details['name'] }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-question-circle mr-2"></i>
                        Need Help?
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Why connect my accounts?</h6>
                            <p class="text-muted">
                                Connecting your social media accounts allows us to automatically publish approved posts
                                on your behalf, saving you time and ensuring your content goes live exactly when scheduled.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Is it secure?</h6>
                            <p class="text-muted">
                                Yes! All access tokens are encrypted using industry-standard encryption before being stored.
                                We only request the minimum permissions needed to publish posts and never store your password.
                            </p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>What permissions do you need?</h6>
                            <p class="text-muted">
                                We only request permissions to create and publish posts on your behalf. We cannot access
                                your private messages, delete content, or perform any administrative actions.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Can I disconnect anytime?</h6>
                            <p class="text-muted">
                                Absolutely! You can disconnect any account at any time. This will revoke our access to that
                                platform immediately. You can always reconnect later if needed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

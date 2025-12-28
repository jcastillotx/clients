<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-1">API Integrations Status</h5>
            <p class="text-muted mb-0">Test connections to external services and connect OAuth-based integrations.</p>
        </div>
        <a href="{{ route('admin.settings.api') }}" class="btn btn-primary">
            <i class="fas fa-key me-1"></i> Manage API Keys
        </a>
    </div>

    <div class="row">
        {{-- Payment Integrations --}}
        <div class="col-12 mb-4">
            <h6 class="text-uppercase text-muted mb-3">Payment Services</h6>
            <div class="row">
                {{-- Stripe --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-stripe fa-2x text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">Stripe</h6>
                                    <small class="text-muted">Payment processing</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['stripe']))
                                <div class="mb-2">
                                    @if($integrationStatus['stripe']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['stripe']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['stripe']['message'])
                                    <small class="{{ $integrationStatus['stripe']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['stripe']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('stripe')" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="testIntegration('stripe')">Test Connection</span>
                                        <span wire:loading wire:target="testIntegration('stripe')">Testing...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- PayPal --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-paypal fa-2x text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">PayPal</h6>
                                    <small class="text-muted">Payment processing</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['paypal']))
                                <div class="mb-2">
                                    @if($integrationStatus['paypal']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['paypal']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['paypal']['message'])
                                    <small class="{{ $integrationStatus['paypal']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['paypal']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['paypal']['configured'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('paypal')" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="testIntegration('paypal')">Test Connection</span>
                                            <span wire:loading wire:target="testIntegration('paypal')">Testing...</span>
                                        </button>
                                    @else
                                        <small class="text-muted">Configure in Payment tab</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- AI Services --}}
        <div class="col-12 mb-4">
            <h6 class="text-uppercase text-muted mb-3">AI Services</h6>
            <div class="row">
                {{-- OpenAI --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-robot fa-2x text-success me-2"></i>
                                <div>
                                    <h6 class="mb-0">OpenAI</h6>
                                    <small class="text-muted">GPT models & embeddings</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['openai']))
                                <div class="mb-2">
                                    @if($integrationStatus['openai']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['openai']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['openai']['message'])
                                    <small class="{{ $integrationStatus['openai']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['openai']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('openai')" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="testIntegration('openai')">Test Connection</span>
                                        <span wire:loading wire:target="testIntegration('openai')">Testing...</span>
                                    </button>
                                    <a href="{{ route('admin.ai.providers') }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Storage Integrations --}}
        <div class="col-12 mb-4">
            <h6 class="text-uppercase text-muted mb-3">Cloud Storage</h6>
            <div class="row">
                {{-- Dropbox --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-dropbox fa-2x text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">Dropbox</h6>
                                    <small class="text-muted">Cloud file storage</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['dropbox']))
                                <div class="mb-2">
                                    @if($integrationStatus['dropbox']['configured'])
                                        <span class="badge bg-info">App Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['dropbox']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['dropbox']['message'])
                                    <small class="{{ $integrationStatus['dropbox']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['dropbox']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['dropbox']['configured'] && ($integrationStatus['dropbox']['can_connect'] ?? false))
                                        <a href="{{ $integrationStatus['dropbox']['oauth_url'] ?? '#' }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-link me-1"></i> Connect Account
                                        </a>
                                    @elseif(!$integrationStatus['dropbox']['configured'])
                                        <small class="text-muted">Set DROPBOX_APP_KEY and DROPBOX_APP_SECRET in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Google Drive --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-google-drive fa-2x text-warning me-2"></i>
                                <div>
                                    <h6 class="mb-0">Google Drive</h6>
                                    <small class="text-muted">Cloud file storage</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['google_drive']))
                                <div class="mb-2">
                                    @if($integrationStatus['google_drive']['configured'])
                                        <span class="badge bg-info">App Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['google_drive']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['google_drive']['message'])
                                    <small class="{{ $integrationStatus['google_drive']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['google_drive']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['google_drive']['configured'] && ($integrationStatus['google_drive']['can_connect'] ?? false))
                                        <a href="{{ $integrationStatus['google_drive']['oauth_url'] ?? '#' }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-link me-1"></i> Connect Account
                                        </a>
                                    @elseif(!$integrationStatus['google_drive']['configured'])
                                        <small class="text-muted">Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Brand Monitoring APIs --}}
        <div class="col-12 mb-4">
            <h6 class="text-uppercase text-muted mb-3">Brand Monitoring</h6>
            <div class="row">
                {{-- NewsAPI --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-newspaper fa-2x text-info me-2"></i>
                                <div>
                                    <h6 class="mb-0">NewsAPI</h6>
                                    <small class="text-muted">News monitoring</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['newsapi']))
                                <div class="mb-2">
                                    @if($integrationStatus['newsapi']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['newsapi']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['newsapi']['message'])
                                    <small class="{{ $integrationStatus['newsapi']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['newsapi']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['newsapi']['configured'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('newsapi')" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="testIntegration('newsapi')">Test Connection</span>
                                            <span wire:loading wire:target="testIntegration('newsapi')">Testing...</span>
                                        </button>
                                    @else
                                        <small class="text-muted">Set NEWSAPI_KEY in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- YouTube --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-youtube fa-2x text-danger me-2"></i>
                                <div>
                                    <h6 class="mb-0">YouTube</h6>
                                    <small class="text-muted">Video monitoring</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['youtube']))
                                <div class="mb-2">
                                    @if($integrationStatus['youtube']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['youtube']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['youtube']['message'])
                                    <small class="{{ $integrationStatus['youtube']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['youtube']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['youtube']['configured'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('youtube')" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="testIntegration('youtube')">Test Connection</span>
                                            <span wire:loading wire:target="testIntegration('youtube')">Testing...</span>
                                        </button>
                                    @else
                                        <small class="text-muted">Set YOUTUBE_API_KEY in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Yelp --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-yelp fa-2x text-danger me-2"></i>
                                <div>
                                    <h6 class="mb-0">Yelp</h6>
                                    <small class="text-muted">Review monitoring</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['yelp']))
                                <div class="mb-2">
                                    @if($integrationStatus['yelp']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['yelp']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['yelp']['message'])
                                    <small class="{{ $integrationStatus['yelp']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['yelp']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['yelp']['configured'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('yelp')" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="testIntegration('yelp')">Test Connection</span>
                                            <span wire:loading wire:target="testIntegration('yelp')">Testing...</span>
                                        </button>
                                    @else
                                        <small class="text-muted">Set YELP_API_KEY in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Google Places --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-map-marker-alt fa-2x text-success me-2"></i>
                                <div>
                                    <h6 class="mb-0">Google Places</h6>
                                    <small class="text-muted">Review monitoring</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['google_places']))
                                <div class="mb-2">
                                    @if($integrationStatus['google_places']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['google_places']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['google_places']['message'])
                                    <small class="{{ $integrationStatus['google_places']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['google_places']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['google_places']['configured'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('google_places')" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="testIntegration('google_places')">Test Connection</span>
                                            <span wire:loading wire:target="testIntegration('google_places')">Testing...</span>
                                        </button>
                                    @else
                                        <small class="text-muted">Set GOOGLE_PLACES_API_KEY in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Social Media --}}
        <div class="col-12 mb-4">
            <h6 class="text-uppercase text-muted mb-3">Social Media Publishing</h6>
            <div class="row">
                {{-- Facebook --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-facebook fa-2x text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">Facebook</h6>
                                    <small class="text-muted">Page publishing</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['facebook']))
                                <div class="mb-2">
                                    @if($integrationStatus['facebook']['configured'])
                                        <span class="badge bg-info">App Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['facebook']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['facebook']['message'])
                                    <small class="{{ $integrationStatus['facebook']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['facebook']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['facebook']['configured'] && ($integrationStatus['facebook']['can_connect'] ?? false))
                                        <a href="{{ $integrationStatus['facebook']['oauth_url'] ?? '#' }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-link me-1"></i> Connect Account
                                        </a>
                                    @elseif(!$integrationStatus['facebook']['configured'])
                                        <small class="text-muted">Set FACEBOOK_CLIENT_ID and FACEBOOK_CLIENT_SECRET in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- LinkedIn --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-linkedin fa-2x text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">LinkedIn</h6>
                                    <small class="text-muted">Company page publishing</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['linkedin']))
                                <div class="mb-2">
                                    @if($integrationStatus['linkedin']['configured'])
                                        <span class="badge bg-info">App Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['linkedin']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['linkedin']['message'])
                                    <small class="{{ $integrationStatus['linkedin']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['linkedin']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['linkedin']['configured'] && ($integrationStatus['linkedin']['can_connect'] ?? false))
                                        <a href="{{ $integrationStatus['linkedin']['oauth_url'] ?? '#' }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-link me-1"></i> Connect Account
                                        </a>
                                    @elseif(!$integrationStatus['linkedin']['configured'])
                                        <small class="text-muted">Set LINKEDIN_CLIENT_ID and LINKEDIN_CLIENT_SECRET in .env</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notification Services --}}
        <div class="col-12 mb-4">
            <h6 class="text-uppercase text-muted mb-3">Notifications</h6>
            <div class="row">
                {{-- Slack --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fab fa-slack fa-2x text-purple me-2" style="color: #4A154B;"></i>
                                <div>
                                    <h6 class="mb-0">Slack</h6>
                                    <small class="text-muted">Team notifications</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['slack']))
                                <div class="mb-2">
                                    @if($integrationStatus['slack']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['slack']['connected'])
                                        <span class="badge bg-success">Valid</span>
                                    @endif
                                </div>
                                @if($integrationStatus['slack']['message'])
                                    <small class="{{ $integrationStatus['slack']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['slack']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['slack']['configured'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('slack')" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="testIntegration('slack')">Validate</span>
                                            <span wire:loading wire:target="testIntegration('slack')">Validating...</span>
                                        </button>
                                    @else
                                        <small class="text-muted">Configure in Notifications tab</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Twilio --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-sms fa-2x text-danger me-2"></i>
                                <div>
                                    <h6 class="mb-0">Twilio</h6>
                                    <small class="text-muted">SMS notifications</small>
                                </div>
                            </div>
                            @if(isset($integrationStatus['twilio']))
                                <div class="mb-2">
                                    @if($integrationStatus['twilio']['configured'])
                                        <span class="badge bg-info">Configured</span>
                                    @else
                                        <span class="badge bg-secondary">Not Configured</span>
                                    @endif
                                    @if($integrationStatus['twilio']['connected'])
                                        <span class="badge bg-success">Connected</span>
                                    @endif
                                </div>
                                @if($integrationStatus['twilio']['message'])
                                    <small class="{{ $integrationStatus['twilio']['connected'] ? 'text-success' : 'text-danger' }}">
                                        {{ $integrationStatus['twilio']['message'] }}
                                    </small>
                                @endif
                                <div class="mt-2">
                                    @if($integrationStatus['twilio']['configured'])
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="testIntegration('twilio')" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="testIntegration('twilio')">Test Connection</span>
                                            <span wire:loading wire:target="testIntegration('twilio')">Testing...</span>
                                        </button>
                                    @else
                                        <small class="text-muted">Configure in Notifications tab</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="button" class="btn btn-outline-secondary" wire:click="loadIntegrationStatus">
            <i class="fas fa-sync-alt me-1"></i> Refresh Status
        </button>
    </div>
</div>

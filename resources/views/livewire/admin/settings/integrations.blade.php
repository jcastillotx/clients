<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-1">Integration Status</h5>
            <p class="text-muted mb-0">Test connections to core services and view integration status.</p>
        </div>
    </div>

    {{-- API Keys Management Card --}}
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-key fa-2x me-3"></i>
            <div class="flex-grow-1">
                <h6 class="mb-1">API Keys & Credentials</h6>
                <p class="mb-0 small">Configure API keys for Brand Monitoring, SEO Tools, and Social Media integrations.</p>
            </div>
            <a href="{{ route('admin.settings.integrations') }}" class="btn btn-primary">
                <i class="fas fa-cog me-1"></i> Manage API Keys
            </a>
        </div>
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
                {{-- AI Providers Card --}}
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-robot fa-2x text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">AI Providers</h6>
                                    <small class="text-muted">OpenAI, Claude, Gemini, Grok & more</small>
                                </div>
                            </div>
                            <p class="text-muted small mb-3">
                                Manage all AI provider configurations including API keys, model selection, cost tracking, and priority ordering.
                            </p>
                            <a href="{{ route('admin.ai.providers') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-cog me-1"></i> Manage AI Providers
                            </a>
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
                                        <small class="text-muted">Configure in Storage tab</small>
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
                                        <small class="text-muted">Configure in Storage tab</small>
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

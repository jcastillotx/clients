<div>
    <div class="mb-4">
        <h5>Brand Monitoring API Configuration</h5>
        <p class="text-muted small">Configure API keys for brand monitoring services. Most have generous free tiers.</p>
    </div>

    <form wire:submit.prevent="saveBrandMonitoringSettings">
        {{-- News Monitoring --}}
        <h6 class="text-uppercase text-muted mb-3">News Monitoring</h6>

        {{-- NewsAPI --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-newspaper text-info me-2"></i>
                    <h6 class="mb-0">NewsAPI</h6>
                    <span class="badge bg-success ms-2">Free: 100 req/day</span>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'newsapi'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.newsapi_api_key" placeholder="NewsAPI Key">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newsapi_enabled" wire:model="brandMonitoring.newsapi_enabled">
                    <label class="form-check-label" for="newsapi_enabled">Enabled</label>
                </div>
            </div>
        </div>

        <hr>

        {{-- Social Monitoring --}}
        <h6 class="text-uppercase text-muted mb-3">Social Media Monitoring</h6>

        {{-- YouTube --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-youtube text-danger me-2"></i>
                    <h6 class="mb-0">YouTube Data API</h6>
                    <span class="badge bg-success ms-2">Free: 10k units/day</span>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'youtube'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.youtube_api_key" placeholder="YouTube API Key">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="youtube_enabled" wire:model="brandMonitoring.youtube_enabled">
                    <label class="form-check-label" for="youtube_enabled">Enabled</label>
                </div>
            </div>
        </div>

        {{-- Reddit --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-reddit text-orange me-2" style="color: #FF4500;"></i>
                    <h6 class="mb-0">Reddit API</h6>
                    <span class="badge bg-success ms-2">Free: 60 req/min</span>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Client ID</label>
                <input type="text" class="form-control" wire:model="brandMonitoring.reddit_client_id" placeholder="Reddit Client ID">
            </div>
            <div class="col-md-4">
                <label class="form-label">Client Secret</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.reddit_client_secret" placeholder="Reddit Client Secret">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="reddit_enabled" wire:model="brandMonitoring.reddit_enabled">
                    <label class="form-check-label" for="reddit_enabled">Enabled</label>
                </div>
            </div>
        </div>

        {{-- Facebook Mentions --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-facebook text-primary me-2"></i>
                    <h6 class="mb-0">Facebook Graph API (Mentions)</h6>
                </div>
            </div>
            <div class="col-md-8">
                <label class="form-label">Page Access Token</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.facebook_access_token" placeholder="Facebook Page Access Token">
                <small class="text-muted">Long-lived page access token for monitoring mentions</small>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="facebook_enabled" wire:model="brandMonitoring.facebook_enabled">
                    <label class="form-check-label" for="facebook_enabled">Enabled</label>
                </div>
            </div>
        </div>

        <hr>

        {{-- Review Monitoring --}}
        <h6 class="text-uppercase text-muted mb-3">Review Monitoring</h6>

        {{-- Yelp --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-yelp text-danger me-2"></i>
                    <h6 class="mb-0">Yelp Fusion API</h6>
                    <span class="badge bg-success ms-2">Free: 5k req/day</span>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'yelp'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.yelp_api_key" placeholder="Yelp API Key">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="yelp_enabled" wire:model="brandMonitoring.yelp_enabled">
                    <label class="form-check-label" for="yelp_enabled">Enabled</label>
                </div>
            </div>
        </div>

        {{-- Google Places --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-map-marker-alt text-success me-2"></i>
                    <h6 class="mb-0">Google Places API</h6>
                    <span class="badge bg-success ms-2">Free: $200 credit/mo</span>
                    @include('livewire.admin.settings.partials.api-test-button', ['provider' => 'google_places'])
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.google_places_api_key" placeholder="Google Places API Key">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="google_places_enabled" wire:model="brandMonitoring.google_places_enabled">
                    <label class="form-check-label" for="google_places_enabled">Enabled</label>
                </div>
            </div>
        </div>

        {{-- Trustpilot --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-star text-success me-2"></i>
                    <h6 class="mb-0">Trustpilot</h6>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.trustpilot_api_key" placeholder="Trustpilot API Key">
            </div>
            <div class="col-md-4">
                <label class="form-label">API Secret</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.trustpilot_api_secret" placeholder="Trustpilot API Secret">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="trustpilot_enabled" wire:model="brandMonitoring.trustpilot_enabled">
                    <label class="form-check-label" for="trustpilot_enabled">Enabled</label>
                </div>
            </div>
        </div>

        {{-- G2 --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-building text-info me-2"></i>
                    <h6 class="mb-0">G2 Crowd</h6>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.g2_api_key" placeholder="G2 API Key">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="g2_enabled" wire:model="brandMonitoring.g2_enabled">
                    <label class="form-check-label" for="g2_enabled">Enabled</label>
                </div>
            </div>
        </div>

        {{-- Capterra --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-laptop text-primary me-2"></i>
                    <h6 class="mb-0">Capterra</h6>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.capterra_api_key" placeholder="Capterra API Key">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="capterra_enabled" wire:model="brandMonitoring.capterra_enabled">
                    <label class="form-check-label" for="capterra_enabled">Enabled</label>
                </div>
            </div>
        </div>

        <hr>

        {{-- Web Search --}}
        <h6 class="text-uppercase text-muted mb-3">Web Search</h6>

        {{-- Google Custom Search --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-google text-primary me-2"></i>
                    <h6 class="mb-0">Google Custom Search</h6>
                    <span class="badge bg-success ms-2">Free: 100 queries/day</span>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.google_search_api_key" placeholder="Google API Key">
            </div>
            <div class="col-md-4">
                <label class="form-label">Search Engine ID</label>
                <input type="text" class="form-control" wire:model="brandMonitoring.google_search_engine_id" placeholder="Custom Search Engine ID">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="google_search_enabled" wire:model="brandMonitoring.google_search_enabled">
                    <label class="form-check-label" for="google_search_enabled">Enabled</label>
                </div>
            </div>
        </div>

        {{-- Bing Search --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-microsoft text-info me-2"></i>
                    <h6 class="mb-0">Bing Web Search</h6>
                    <span class="badge bg-success ms-2">Free: 1k queries/mo</span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="password" class="form-control" wire:model="brandMonitoring.bing_search_api_key" placeholder="Bing Search API Key">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="bing_search_enabled" wire:model="brandMonitoring.bing_search_enabled">
                    <label class="form-check-label" for="bing_search_enabled">Enabled</label>
                </div>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveBrandMonitoringSettings">
                    <i class="fas fa-save me-1"></i> Save Brand Monitoring Settings
                </span>
                <span wire:loading wire:target="saveBrandMonitoringSettings">
                    <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                </span>
            </button>
        </div>
    </form>
</div>

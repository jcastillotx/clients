<div>
    <div class="mb-4">
        <h5>Social Media API Configuration</h5>
        <p class="text-muted small">Configure API credentials for social media publishing and management.</p>
    </div>

    <form wire:submit.prevent="saveSocialSettings">
        {{-- Facebook --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-facebook fa-lg text-primary me-2"></i>
                    <h6 class="mb-0">Facebook / Meta</h6>
                </div>
                <small class="text-muted">Required for Facebook Page publishing</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">App ID</label>
                <input type="text" class="form-control" wire:model="social.facebook_client_id" placeholder="Facebook App ID">
            </div>
            <div class="col-md-6">
                <label class="form-label">App Secret</label>
                <input type="password" class="form-control" wire:model="social.facebook_client_secret" placeholder="Facebook App Secret">
            </div>
        </div>

        {{-- Instagram --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-instagram fa-lg me-2" style="color: #E4405F;"></i>
                    <h6 class="mb-0">Instagram (via Facebook)</h6>
                </div>
                <small class="text-muted">Uses Facebook Graph API for Instagram Business accounts</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">App ID</label>
                <input type="text" class="form-control" wire:model="social.instagram_client_id" placeholder="Instagram/Facebook App ID">
            </div>
            <div class="col-md-6">
                <label class="form-label">App Secret</label>
                <input type="password" class="form-control" wire:model="social.instagram_client_secret" placeholder="Instagram/Facebook App Secret">
            </div>
        </div>

        {{-- LinkedIn --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-linkedin fa-lg text-primary me-2"></i>
                    <h6 class="mb-0">LinkedIn</h6>
                </div>
                <small class="text-muted">Required for LinkedIn publishing</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Client ID</label>
                <input type="text" class="form-control" wire:model="social.linkedin_client_id" placeholder="LinkedIn Client ID">
            </div>
            <div class="col-md-6">
                <label class="form-label">Client Secret</label>
                <input type="password" class="form-control" wire:model="social.linkedin_client_secret" placeholder="LinkedIn Client Secret">
            </div>
        </div>

        {{-- Twitter/X --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-x-twitter fa-lg me-2"></i>
                    <h6 class="mb-0">X (Twitter)</h6>
                </div>
                <small class="text-muted">Requires Twitter API v2 access</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">API Key</label>
                <input type="text" class="form-control" wire:model="social.twitter_api_key" placeholder="API Key">
            </div>
            <div class="col-md-3">
                <label class="form-label">API Secret</label>
                <input type="password" class="form-control" wire:model="social.twitter_api_secret" placeholder="API Secret">
            </div>
            <div class="col-md-3">
                <label class="form-label">Access Token</label>
                <input type="text" class="form-control" wire:model="social.twitter_access_token" placeholder="Access Token">
            </div>
            <div class="col-md-3">
                <label class="form-label">Access Secret</label>
                <input type="password" class="form-control" wire:model="social.twitter_access_secret" placeholder="Access Token Secret">
            </div>
        </div>

        {{-- TikTok --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-tiktok fa-lg me-2"></i>
                    <h6 class="mb-0">TikTok</h6>
                </div>
                <small class="text-muted">Required for TikTok Business account publishing</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Client Key</label>
                <input type="text" class="form-control" wire:model="social.tiktok_client_key" placeholder="TikTok Client Key">
            </div>
            <div class="col-md-6">
                <label class="form-label">Client Secret</label>
                <input type="password" class="form-control" wire:model="social.tiktok_client_secret" placeholder="TikTok Client Secret">
            </div>
        </div>

        {{-- Threads --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-at fa-lg me-2"></i>
                    <h6 class="mb-0">Threads (Meta)</h6>
                </div>
                <small class="text-muted">Uses Instagram API for Threads publishing</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Client ID</label>
                <input type="text" class="form-control" wire:model="social.threads_client_id" placeholder="Threads/Instagram App ID">
            </div>
            <div class="col-md-6">
                <label class="form-label">Client Secret</label>
                <input type="password" class="form-control" wire:model="social.threads_client_secret" placeholder="Threads/Instagram App Secret">
            </div>
        </div>

        {{-- Pinterest --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-pinterest fa-lg text-danger me-2"></i>
                    <h6 class="mb-0">Pinterest</h6>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">App ID</label>
                <input type="text" class="form-control" wire:model="social.pinterest_app_id" placeholder="Pinterest App ID">
            </div>
            <div class="col-md-6">
                <label class="form-label">App Secret</label>
                <input type="password" class="form-control" wire:model="social.pinterest_app_secret" placeholder="Pinterest App Secret">
            </div>
        </div>

        {{-- Bluesky --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-cloud fa-lg text-info me-2"></i>
                    <h6 class="mb-0">Bluesky</h6>
                </div>
                <small class="text-muted">Uses AT Protocol - create an App Password</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Handle/Identifier</label>
                <input type="text" class="form-control" wire:model="social.bluesky_identifier" placeholder="yourhandle.bsky.social">
            </div>
            <div class="col-md-6">
                <label class="form-label">App Password</label>
                <input type="password" class="form-control" wire:model="social.bluesky_password" placeholder="App Password">
            </div>
        </div>

        {{-- Mastodon --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-2">
                    <i class="fab fa-mastodon fa-lg me-2" style="color: #6364FF;"></i>
                    <h6 class="mb-0">Mastodon</h6>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Instance URL</label>
                <input type="text" class="form-control" wire:model="social.mastodon_instance" placeholder="https://mastodon.social">
            </div>
            <div class="col-md-6">
                <label class="form-label">Access Token</label>
                <input type="password" class="form-control" wire:model="social.mastodon_access_token" placeholder="Mastodon Access Token">
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveSocialSettings">
                    <i class="fas fa-save me-1"></i> Save Social Media Settings
                </span>
                <span wire:loading wire:target="saveSocialSettings">
                    <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                </span>
            </button>
        </div>
    </form>
</div>

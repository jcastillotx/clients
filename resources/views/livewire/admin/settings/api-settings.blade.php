<div>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">Integration Settings</h1>
                <p class="text-muted">Manage API keys and credentials for brand monitoring and social media integrations. Keys are stored securely with encryption.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.ai.providers') }}" class="btn btn-outline-primary">
                    <i class="fas fa-robot me-1"></i> Manage AI Providers
                </a>
            </div>
        </div>

        @if(session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'brand_monitoring' ? 'active' : '' }}" wire:click="setTab('brand_monitoring')">
                            <i class="fas fa-chart-line me-1"></i> Brand Monitoring
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'seo' ? 'active' : '' }}" wire:click="setTab('seo')">
                            <i class="fas fa-search me-1"></i> SEO Integrations
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'social' ? 'active' : '' }}" wire:click="setTab('social')">
                            <i class="fas fa-share-alt me-1"></i> Social Media
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'storage' ? 'active' : '' }}" wire:click="setTab('storage')">
                            <i class="fas fa-cloud me-1"></i> Cloud Storage
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                {{-- Brand Monitoring Tab --}}
                @if($tab === 'brand_monitoring')
                    @include('livewire.admin.settings.api-settings-brand')
                @endif

                {{-- SEO Integrations Tab --}}
                @if($tab === 'seo')
                    @include('livewire.admin.settings.api-settings-seo')
                @endif

                {{-- Social Media Tab --}}
                @if($tab === 'social')
                    @include('livewire.admin.settings.api-settings-social')
                @endif

                {{-- Cloud Storage Tab --}}
                @if($tab === 'storage')
                    @include('livewire.admin.settings.api-settings-storage')
                @endif
            </div>
        </div>
    </div>
</div>

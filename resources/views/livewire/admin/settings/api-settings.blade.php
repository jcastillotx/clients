<div>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">API Configuration</h1>
                <p class="text-muted">Manage API keys and credentials for all integrations. Keys are stored securely with encryption.</p>
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
                        <button type="button" class="nav-link {{ $tab === 'ai' ? 'active' : '' }}" wire:click="setTab('ai')">
                            <i class="fas fa-robot me-1"></i> AI Providers
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'brand_monitoring' ? 'active' : '' }}" wire:click="setTab('brand_monitoring')">
                            <i class="fas fa-chart-line me-1"></i> Brand Monitoring
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ $tab === 'social' ? 'active' : '' }}" wire:click="setTab('social')">
                            <i class="fas fa-share-alt me-1"></i> Social Media
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                {{-- AI Providers Tab --}}
                @if($tab === 'ai')
                    @include('livewire.admin.settings.api-settings-ai')
                @endif

                {{-- Brand Monitoring Tab --}}
                @if($tab === 'brand_monitoring')
                    @include('livewire.admin.settings.api-settings-brand')
                @endif

                {{-- Social Media Tab --}}
                @if($tab === 'social')
                    @include('livewire.admin.settings.api-settings-social')
                @endif
            </div>
        </div>
    </div>
</div>

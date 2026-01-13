<div>
    <h2 class="mb-3">System Settings</h2>

    {{-- Flash Messages & Validation Errors --}}
    @include('partials.flash-messages')

    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" role="tablist">
                @php
                    $tabs = [
                        'general' => ['label' => 'General', 'icon' => 'fas fa-cog'],
                        'email' => ['label' => 'Email', 'icon' => 'fas fa-envelope'],
                        'payment' => ['label' => 'Payment', 'icon' => 'fas fa-credit-card'],
                        'storage' => ['label' => 'Storage', 'icon' => 'fas fa-hdd'],
                        'notifications' => ['label' => 'Notifications', 'icon' => 'fas fa-bell'],
                        'security' => ['label' => 'Security', 'icon' => 'fas fa-shield-alt'],
                        'integrations' => ['label' => 'Integrations', 'icon' => 'fas fa-plug'],
                    ];
                    // Only show Branding and Platform tabs to super admins
                    if ($isSuperAdmin ?? false) {
                        $tabs['branding'] = ['label' => 'Branding', 'icon' => 'fas fa-paint-brush'];
                        $tabs['platform'] = ['label' => 'Platform', 'icon' => 'fas fa-toggle-on'];
                        $tabs['updates'] = ['label' => 'Updates', 'icon' => 'fas fa-code-branch'];
                    }
                @endphp
                @foreach($tabs as $k => $config)
                    <li class="nav-item">
                        <a href="#" class="nav-link {{ $tab === $k ? 'active' : '' }}" wire:click.prevent="setTab('{{ $k }}')">
                            @if($config['icon'])
                                <i class="{{ $config['icon'] }} me-1"></i>
                            @endif
                            {{ $config['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            @if($tab === 'general')
                @include('livewire.admin.settings.general')
            @elseif($tab === 'email')
                @include('livewire.admin.settings.email')
            @elseif($tab === 'payment')
                @include('livewire.admin.settings.payment')
            @elseif($tab === 'storage')
                @include('livewire.admin.settings.storage')
            @elseif($tab === 'notifications')
                @include('livewire.admin.settings.notifications')
            @elseif($tab === 'security')
                @include('livewire.admin.settings.security')
            @elseif($tab === 'branding')
                @include('livewire.admin.settings.branding')
            @elseif($tab === 'integrations')
                @include('livewire.admin.settings.integrations')
            @elseif($tab === 'platform')
                @include('livewire.admin.settings.platform')
            @elseif($tab === 'updates')
                @include('livewire.admin.settings.updates')
            @endif
        </div>
    </div>
</div>


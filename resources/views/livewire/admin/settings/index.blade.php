<div>
    <h2 class="mb-3">System Settings</h2>

    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" role="tablist">
                @php
                    $tabs = [
                        'general' => ['label' => 'General', 'icon' => null],
                        'email' => ['label' => 'Email', 'icon' => null],
                        'payment' => ['label' => 'Payment', 'icon' => null],
                        'storage' => ['label' => 'Storage', 'icon' => null],
                        'notifications' => ['label' => 'Notifications', 'icon' => null],
                        'security' => ['label' => 'Security', 'icon' => null],
                        'branding' => ['label' => 'Branding', 'icon' => null],
                        'integrations' => ['label' => 'Integrations', 'icon' => 'fas fa-plug'],
                    ];
                    // Only show Platform tab to super admins
                    if ($isSuperAdmin ?? false) {
                        $tabs['platform'] = ['label' => 'Platform', 'icon' => 'fas fa-toggle-on'];
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
            @endif
        </div>
    </div>
</div>


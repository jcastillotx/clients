<x-app-layout>
    <x-slot name="header">System Settings</x-slot>

    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" role="tablist">
                @foreach(['general' => 'General', 'email' => 'Email', 'payment' => 'Payment', 'storage' => 'Storage', 'notifications' => 'Notifications', 'security' => 'Security', 'branding' => 'Branding', 'integrations' => 'Integrations'] as $k => $label)
                    <li class="nav-item">
                        <a href="#" class="nav-link {{ $tab === $k ? 'active' : '' }}" wire:click.prevent="setTab('{{ $k }}')">
                            @if($k === 'integrations')
                                <i class="fas fa-plug me-1"></i>
                            @endif
                            {{ $label }}
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
            @endif
        </div>
    </div>
</x-app-layout>


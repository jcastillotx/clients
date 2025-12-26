<x-app-layout>
    <x-slot name="header">System Settings</x-slot>

    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" role="tablist">
                @foreach(['general' => 'General Settings', 'email' => 'Email Settings', 'payment' => 'Payment Settings', 'storage' => 'Storage Settings', 'notifications' => 'Notification Settings', 'security' => 'Security Settings', 'branding' => 'Branding'] as $k => $label)
                    <li class="nav-item">
                        <a href="#" class="nav-link {{ $tab === $k ? 'active' : '' }}" wire:click.prevent="setTab('{{ $k }}')">
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
            @endif
        </div>
    </div>
</x-app-layout>


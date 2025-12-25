<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">System Settings</h2>
            <div class="text-muted small">System-wide configuration stored in the database (cached for performance).</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($tab==='general') active @endif" wire:click="switchTab('general')" type="button" role="tab">General Settings</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($tab==='email') active @endif" wire:click="switchTab('email')" type="button" role="tab">Email Settings</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($tab==='payment') active @endif" wire:click="switchTab('payment')" type="button" role="tab">Payment Settings</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($tab==='storage') active @endif" wire:click="switchTab('storage')" type="button" role="tab">Storage Settings</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($tab==='notifications') active @endif" wire:click="switchTab('notifications')" type="button" role="tab">Notification Settings</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($tab==='security') active @endif" wire:click="switchTab('security')" type="button" role="tab">Security Settings</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($tab==='branding') active @endif" wire:click="switchTab('branding')" type="button" role="tab">Branding</button>
                </li>
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
</div>


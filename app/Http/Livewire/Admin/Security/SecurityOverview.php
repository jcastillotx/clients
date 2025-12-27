<?php

namespace App\Http\Livewire\Admin\Security;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SecurityOverview extends Component
{
    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        return view('livewire.admin.security.security-overview', [
            'allowlist' => config('security.admin_ip_allowlist', []),
            'enforce2fa' => (bool) config('security.enforce_admin_2fa', true),
            'retentionDays' => (int) config('security.audit_retention_days', 365),
        ])->layout('layouts.admin', ['title' => 'Security settings']);
    }
}

<?php

namespace App\Http\Livewire\Admin\Security;

use App\Jobs\Security\ProcessDataPrivacyRequestJob;
use App\Models\DataPrivacyRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PrivacyRequests extends Component
{
    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
    }

    public function process(int $id): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $r = DataPrivacyRequest::query()->findOrFail($id);
        abort_unless($r->status === 'pending', 422);
        ProcessDataPrivacyRequestJob::dispatch($r->id);
        session()->flash('success', 'Processing queued.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $requests = DataPrivacyRequest::query()
            ->with('user')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('livewire.admin.security.privacy-requests', [
            'requests' => $requests,
        ])->layout('layouts.admin', ['title' => 'Privacy requests']);
    }
}

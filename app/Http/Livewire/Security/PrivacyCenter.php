<?php

namespace App\Http\Livewire\Security;

use App\Jobs\Security\ProcessDataPrivacyRequestJob;
use App\Models\DataPrivacyRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PrivacyCenter extends Component
{
    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);
    }

    public function requestExport(): void
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);

        $row = DataPrivacyRequest::query()->firstOrCreate([
            'user_id' => $u->id,
            'type' => 'export',
            'status' => 'pending',
        ], [
            'notes' => null,
        ]);

        ProcessDataPrivacyRequestJob::dispatch($row->id);
        session()->flash('success', 'Export requested. You can download it once processed.');
    }

    public function requestDeletion(): void
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);

        $row = DataPrivacyRequest::query()->create([
            'user_id' => $u->id,
            'type' => 'delete',
            'status' => 'pending',
            'notes' => 'User initiated deletion request',
        ]);

        ProcessDataPrivacyRequestJob::dispatch($row->id);
        session()->flash('success', 'Deletion requested. If processed, your account will be deactivated.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);

        $requests = DataPrivacyRequest::query()
            ->where('user_id', $u->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('livewire.security.privacy-center', [
            'requests' => $requests,
        ]);
    }
}


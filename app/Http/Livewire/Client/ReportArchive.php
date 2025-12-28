<?php

namespace App\Http\Livewire\Client;

use App\Models\ReportDelivery;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReportArchive extends Component
{
    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);

        $deliveries = ReportDelivery::query()
            ->where('client_id', $u->client_id)
            ->orderByDesc('generated_at')
            ->limit(100)
            ->get();

        return view('livewire.client.report-archive', [
            'deliveries' => $deliveries,
        ])->layout('layouts.app');
    }
}

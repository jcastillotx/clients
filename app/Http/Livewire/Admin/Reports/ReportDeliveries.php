<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Models\ReportDelivery;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReportDeliveries extends Component
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

        $deliveries = ReportDelivery::query()
            ->with(['schedule', 'template', 'client'])
            ->orderByDesc('generated_at')
            ->limit(200)
            ->get();

        return view('livewire.admin.reports.report-deliveries', [
            'deliveries' => $deliveries,
        ])->layout('layouts.admin', ['title' => 'Report deliveries']);
    }
}


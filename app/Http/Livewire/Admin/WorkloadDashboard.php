<?php

namespace App\Http\Livewire\Admin;

use App\Services\Estimates\WorkloadCapacityService;
use Livewire\Component;

class WorkloadDashboard extends Component
{
    public function render(WorkloadCapacityService $workloadService)
    {
        $workload = $workloadService->getCurrentWorkload();
        $availableStaff = $workloadService->getAvailableStaffForAssignment(10);

        return view('livewire.admin.workload-dashboard', [
            'workload' => $workload,
            'availableStaff' => $availableStaff,
        ])->layout('layouts.admin', ['title' => 'Team Workload']);
    }
}

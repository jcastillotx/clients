<?php

namespace App\Http\Livewire\Admin\Reports;

use Livewire\Component;

class ReportDashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.reports.dashboard')->layout('layouts.admin', ['title' => 'Reports']);
    }
}


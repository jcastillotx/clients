<?php

namespace App\Http\Livewire\Projects;

use App\Models\Invoice;
use App\Models\ProjectBudget;
use App\Models\Request as ServiceRequest;
use App\Services\Projects\ProjectBudgetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectBudgets extends Component
{
    public ?int $requestId = null;

    public function recalc(ProjectBudgetService $svc): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        abort_unless($this->requestId, 422);

        $req = ServiceRequest::query()->findOrFail($this->requestId);
        $svc->recalcForRequest($req);
        session()->flash('success', 'Budget recalculated.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $requests = ServiceRequest::query()->orderByDesc('id')->limit(200)->get(['id', 'title', 'client_id']);

        $budgets = ProjectBudget::query()
            ->when($this->requestId, fn ($q) => $q->where('request_id', $this->requestId))
            ->with('request')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $invoiceTotals = [];
        $requestIds = $budgets->pluck('request_id')->filter()->unique()->values()->all();
        if (! empty($requestIds)) {
            $rows = Invoice::query()
                ->whereIn('request_id', $requestIds)
                ->selectRaw('request_id, SUM(amount) as total')
                ->groupBy('request_id')
                ->get();
            foreach ($rows as $r) {
                $invoiceTotals[(int) $r->request_id] = (float) $r->total;
            }
        }

        return view('livewire.projects.project-budgets', [
            'requests' => $requests,
            'budgets' => $budgets,
            'invoiceTotals' => $invoiceTotals,
        ]);
    }
}

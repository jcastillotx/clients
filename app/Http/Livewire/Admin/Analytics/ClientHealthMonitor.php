<?php

namespace App\Http\Livewire\Admin\Analytics;

use App\Models\Client;
use App\Models\ClientHealthSnapshot;
use App\Services\AI\PredictiveAnalyticsService;
use Livewire\Component;
use Livewire\WithPagination;

class ClientHealthMonitor extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function refreshClient(int $clientId, PredictiveAnalyticsService $svc): void
    {
        $client = Client::query()->findOrFail($clientId);
        $svc->generateClientHealthScore($client);
        session()->flash('success', 'Client health updated.');
    }

    public function render()
    {
        $q = Client::query()
            ->orderBy('company_name')
            ->when(trim($this->search) !== '', function ($qq) {
                $s = '%'.trim($this->search).'%';
                $qq->where('company_name', 'like', $s)->orWhere('email', 'like', $s);
            });

        $clients = $q->paginate(25);

        $snapshots = ClientHealthSnapshot::query()
            ->orderByDesc('computed_at')
            ->limit(1000)
            ->get()
            ->groupBy('client_id')
            ->map(fn ($rows) => $rows->sortByDesc('computed_at')->first());

        return view('livewire.admin.analytics.client-health', [
            'clients' => $clients,
            'snapshots' => $snapshots,
        ])->layout('layouts.admin', ['title' => 'Client Health']);
    }
}

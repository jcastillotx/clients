<?php

namespace App\Http\Livewire\Admin\Storage;

use App\Models\Client;
use App\Models\Setting;
use App\Models\StorageConnection;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class StorageOverview extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $provider = 'all';
    public string $status = 'all';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingProvider(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }

    protected function baseQuery(): Builder
    {
        $q = StorageConnection::query()->with('client')->orderByDesc('last_synced_at')->orderByDesc('id');

        // Staff scoping: staff can only view assigned clients.
        $user = auth()->user();
        if ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $q->whereIn('client_id', $user->assignedClientIds());
        }

        if ($this->provider !== 'all') {
            $q->where('provider', $this->provider);
        }
        if ($this->status !== 'all') {
            $q->where('status', $this->status);
        }
        if ($this->search !== '') {
            $s = '%' . $this->search . '%';
            $q->whereHas('client', fn ($c) => $c->where('company_name', 'like', $s)->orWhere('email', 'like', $s))
                ->orWhere('provider', 'like', $s);
        }

        return $q;
    }

    public function render()
    {
        $rows = $this->baseQuery()->paginate(25);

        $all = StorageConnection::query();
        $totalUsed = (int) $all->sum('storage_used');
        $totalKnownLimit = (int) $all->whereNotNull('storage_limit')->sum('storage_limit');

        // Naive S3 cost estimate ($0.023 per GB-month) using current used bytes.
        $s3UsedBytes = (int) StorageConnection::query()->where('provider', 'aws_s3')->sum('storage_used');
        $s3Gb = $s3UsedBytes / (1024 * 1024 * 1024);
        $s3EstimatedMonthly = round($s3Gb * 0.023, 2);

        $quotaByTierMb = (array) Setting::getValue('storage.quota_by_tier_mb', []);
        $quotaByTierBytes = collect($quotaByTierMb)
            ->map(fn ($mb) => (int) $mb * 1024 * 1024)
            ->all();

        $clientTotals = Client::query()
            ->leftJoin('storage_connections', function ($j) {
                $j->on('storage_connections.client_id', '=', 'clients.id')
                    ->whereNull('storage_connections.deleted_at');
            })
            ->selectRaw('clients.id, clients.company_name, clients.tier, SUM(COALESCE(storage_connections.storage_used,0)) as used')
            ->groupBy('clients.id', 'clients.company_name', 'clients.tier')
            ->orderByDesc('used')
            ->limit(50)
            ->get()
            ->map(function ($r) use ($quotaByTierBytes) {
                $tier = (string) ($r->tier ?? 'standard');
                $quota = (int) ($quotaByTierBytes[$tier] ?? 0);
                $used = (int) ($r->used ?? 0);
                $pct = $quota > 0 ? min(200, (int) round(($used / $quota) * 100)) : null;

                return [
                    'client' => (string) $r->company_name,
                    'tier' => $tier,
                    'used' => $used,
                    'quota' => $quota,
                    'pct' => $pct,
                ];
            })
            ->all();

        return view('livewire.admin.storage.overview', [
            'connections' => $rows,
            'totalUsed' => $totalUsed,
            'totalKnownLimit' => $totalKnownLimit,
            's3EstimatedMonthly' => $s3EstimatedMonthly,
            'clientTotals' => $clientTotals,
        ])->layout('layouts.admin', ['title' => 'Storage Overview']);
    }
}


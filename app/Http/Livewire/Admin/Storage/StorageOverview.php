<?php

namespace App\Http\Livewire\Admin\Storage;

use App\Models\Client;
use App\Models\StorageConnection;
use App\Models\StorageSyncLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class StorageOverview extends Component
{
    use WithPagination;

    public string $search = '';

    public string $provider = '';

    public string $status = '';

    protected array $queryString = [
        'search' => ['except' => ''],
        'provider' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('access admin panel'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProvider(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = StorageConnection::query()
            ->with('client:id,company_name,tier,status')
            ->when($this->provider, fn ($q) => $q->where('provider', $this->provider))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->whereHas('client', function ($cq) {
                    $cq->where('company_name', 'like', '%'.$this->search.'%');
                })->orWhere('name', 'like', '%'.$this->search.'%');
            })
            ->orderByDesc('updated_at');

        $connections = $query->paginate(25);

        $stats = [
            'clients' => (int) Client::count(),
            'connections' => (int) StorageConnection::count(),
            'active' => (int) StorageConnection::where('status', 'active')->count(),
            'error' => (int) StorageConnection::where('status', 'error')->count(),
            'used_bytes' => (int) StorageConnection::sum('used_bytes'),
        ];

        $lastFailures = StorageSyncLog::query()
            ->with('connection.client')
            ->where('status', 'failed')
            ->latest('id')
            ->limit(10)
            ->get();

        // Cost tracking (placeholder): if provider == s3, use config value per GB-month.
        $s3Rate = (float) config('storage.costs.s3_per_gb_month', 0.023);
        $s3Bytes = (int) StorageConnection::where('provider', 's3')->sum('used_bytes');
        $stats['s3_estimated_monthly_cost'] = round(($s3Bytes / (1024 ** 3)) * $s3Rate, 2);

        return view('livewire.admin.storage.overview', compact('connections', 'stats', 'lastFailures', 's3Rate'));
    }
}

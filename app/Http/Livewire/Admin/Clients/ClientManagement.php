<?php

namespace App\Http\Livewire\Admin\Clients;

use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ClientManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $status = 'all';

    public string $tier = 'all';

    public ?string $dateFrom = null; // YYYY-MM-DD

    public ?string $dateTo = null;   // YYYY-MM-DD

    /** @var array<int, int> */
    public array $selected = [];

    public bool $selectPage = false;

    public string $bulkAction = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingTier(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    protected function baseQuery(): Builder
    {
        $query = Client::query()
            ->withCount([
                'requests as active_requests_count' => fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']),
            ])
            ->withSum(['payments as total_revenue' => fn ($q) => $q->where('status', 'succeeded')], 'amount')
            ->withMax(['activityLogs as last_activity_at' => 'created_at'])
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(function ($qq) use ($s) {
                    $qq->where('company_name', 'like', $s)
                        ->orWhere('contact_name', 'like', $s)
                        ->orWhere('email', 'like', $s);
                });
            })
            ->when($this->status !== 'all' && $this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->tier !== 'all' && $this->tier !== '', fn ($q) => $q->where('tier', $this->tier))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('company_name');

        return $query;
    }

    public function updatedSelectPage(bool $value): void
    {
        if (! $value) {
            $this->selected = [];

            return;
        }

        $this->selected = $this->baseQuery()
            ->limit(50)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function bulkActivate(): void
    {
        if (empty($this->selected)) {
            return;
        }
        Client::query()->whereIn('id', $this->selected)->update(['status' => 'active']);
        session()->flash('success', 'Selected clients activated.');
        $this->selectPage = false;
        $this->selected = [];
    }

    public function bulkSuspend(): void
    {
        if (empty($this->selected)) {
            return;
        }
        Client::query()->whereIn('id', $this->selected)->update(['status' => 'suspended']);
        session()->flash('success', 'Selected clients suspended.');
        $this->selectPage = false;
        $this->selected = [];
    }

    public function exportCsv()
    {
        $clients = $this->baseQuery()->get();

        $filename = 'clients-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($clients) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Company', 'Contact', 'Email', 'Tier', 'Status',
                'Active Requests', 'Total Revenue', 'Last Activity', 'Created At',
            ]);

            foreach ($clients as $c) {
                fputcsv($out, [
                    $c->company_name,
                    $c->contact_name,
                    $c->email,
                    $c->tier,
                    $c->status,
                    (int) ($c->active_requests_count ?? 0),
                    (float) ($c->total_revenue ?? 0),
                    $c->last_activity_at ? Carbon::parse($c->last_activity_at)->toDateTimeString() : '',
                    $c->created_at?->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf()
    {
        $clients = $this->baseQuery()->get();
        $pdf = Pdf::loadView('admin.clients.export-list-pdf', compact('clients'));

        return response()->streamDownload(fn () => print ($pdf->output()), 'clients-'.now()->format('Ymd-His').'.pdf');
    }

    public function render()
    {
        return view('livewire.admin.clients.index', [
            'clients' => $this->baseQuery()->paginate(15),
            'statuses' => ['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'suspended' => 'Suspended'],
            'tiers' => ['all' => 'All', 'basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium', 'enterprise' => 'Enterprise'],
        ])->layout('layouts.admin', ['title' => 'Clients Management']);
    }
}

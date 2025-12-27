<?php

namespace App\Http\Livewire\Admin\Clients;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;

class ClientDetail extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public Client $client;

    public string $tab = 'overview';

    public string $notes = '';

    public function mount(Client $client): void
    {
        $this->client = $client;
        $this->notes = (string) ($client->notes ?? '');
    }

    public function updated(string $property): void
    {
        if ($property === 'tab') {
            $this->resetPage();
        }
    }

    public function saveNotes(): void
    {
        $this->client->update(['notes' => $this->notes]);
        session()->flash('success', 'Notes saved.');
    }

    public function updateRequestStatus(int $requestId, string $status): void
    {
        $allowed = array_keys(config('client-portal.request_statuses', []));
        if (! in_array($status, $allowed, true)) {
            session()->flash('error', 'Invalid status.');

            return;
        }

        $req = ServiceRequest::query()
            ->where('client_id', $this->client->id)
            ->findOrFail($requestId);

        $req->update(['status' => $status]);
        session()->flash('success', 'Request status updated.');
    }

    public function exportPdf()
    {
        $client = $this->client->load([
            'users',
        ]);

        $stats = [
            'open_requests' => (int) ServiceRequest::query()->where('client_id', $client->id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'invoices_total' => (float) Invoice::query()->where('client_id', $client->id)->sum('amount'),
            'payments_total' => (float) Payment::query()->where('client_id', $client->id)->where('status', 'succeeded')->sum('amount'),
        ];

        $pdf = Pdf::loadView('admin.clients.export-detail-pdf', compact('client', 'stats'));

        return response()->streamDownload(fn () => print ($pdf->output()), 'client-'.$client->id.'-details.pdf');
    }

    public function exportCsv()
    {
        $client = $this->client;
        $filename = 'client-'.$client->id.'-details.csv';

        $openRequests = (int) ServiceRequest::query()
            ->where('client_id', $client->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        $invoicesTotal = (float) Invoice::query()->where('client_id', $client->id)->sum('amount');
        $paymentsTotal = (float) Payment::query()->where('client_id', $client->id)->where('status', 'succeeded')->sum('amount');

        return response()->streamDownload(function () use ($client, $openRequests, $invoicesTotal, $paymentsTotal) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Client ID', $client->id]);
            fputcsv($out, ['Company', $client->company_name]);
            fputcsv($out, ['Contact', $client->contact_name]);
            fputcsv($out, ['Email', $client->email]);
            fputcsv($out, ['Phone', $client->phone]);
            fputcsv($out, ['Tier', $client->tier]);
            fputcsv($out, ['Status', $client->status]);
            fputcsv($out, ['Open Requests', $openRequests]);
            fputcsv($out, ['Total Invoiced', $invoicesTotal]);
            fputcsv($out, ['Total Paid', $paymentsTotal]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $client = $this->client;

        $stats = [
            'active_requests' => (int) ServiceRequest::query()->where('client_id', $client->id)->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'pending_invoices' => (int) Invoice::query()->where('client_id', $client->id)->whereIn('status', ['sent', 'overdue'])->count(),
            'active_contracts' => (int) Contract::query()->where('client_id', $client->id)->where('status', 'active')->count(),
            'revenue' => (float) Payment::query()->where('client_id', $client->id)->where('status', 'succeeded')->sum('amount'),
        ];

        $data = [
            'client' => $client,
            'stats' => $stats,
            'requestStatuses' => config('client-portal.request_statuses', []),
        ];

        if ($this->tab === 'overview') {
            $data['recentActivity'] = ActivityLog::query()
                ->where('client_id', $client->id)
                ->with('user')
                ->latest()
                ->limit(10)
                ->get();
        } elseif ($this->tab === 'requests') {
            $data['requests'] = ServiceRequest::query()
                ->where('client_id', $client->id)
                ->with(['creator', 'assignee'])
                ->latest()
                ->paginate(15);
        } elseif ($this->tab === 'invoices') {
            $data['invoices'] = Invoice::query()
                ->where('client_id', $client->id)
                ->withSum(['payments as total_paid' => fn ($q) => $q->where('status', 'succeeded')], 'amount')
                ->latest()
                ->paginate(15);
        } elseif ($this->tab === 'contracts') {
            $data['contracts'] = Contract::query()
                ->where('client_id', $client->id)
                ->latest()
                ->paginate(15);
        } elseif ($this->tab === 'documents') {
            $data['documents'] = Document::query()
                ->where('client_id', $client->id)
                ->with('uploader')
                ->latest()
                ->paginate(15);
        } elseif ($this->tab === 'storage') {
            // Placeholder for connected storage providers
        } elseif ($this->tab === 'activity') {
            $data['activities'] = ActivityLog::query()
                ->where('client_id', $client->id)
                ->with(['user'])
                ->latest()
                ->paginate(25);
        } elseif ($this->tab === 'notes') {
            // notes handled by $this->notes
        }

        return view('livewire.admin.clients.detail', $data)
            ->layout('layouts.admin', ['title' => 'Client Detail']);
    }
}

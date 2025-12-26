<?php

namespace App\Http\Livewire\Admin\Invoices;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AdminInvoiceManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $tab = 'invoices'; // invoices|payments

    // Invoice filters
    public string $status = 'all';
    public ?int $clientId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $paymentStatus = 'all'; // all|unpaid|partial|paid|overdue|refunded

    // Payments filters
    public string $paymentMethod = 'all';
    public string $paymentState = 'all'; // all|pending|processing|succeeded|failed|refunded|cancelled

    // Manual payment modal
    public bool $showPaymentModal = false;
    public ?int $payInvoiceId = null;
    public string $payAmount = '';
    public string $payMethod = 'check';
    public string $payTransactionId = '';
    public ?string $payProcessedAt = null; // datetime-local
    public bool $paySendReceipt = true;

    public function updatingTab(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingClientId(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }
    public function updatingPaymentStatus(): void { $this->resetPage(); }
    public function updatingPaymentMethod(): void { $this->resetPage(); }
    public function updatingPaymentState(): void { $this->resetPage(); }

    protected function invoicesQuery(): Builder
    {
        $user = auth()->user();
        $staffClientIds = [];
        if ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $staffClientIds = $user->assignedClientIds();
        }

        $q = Invoice::query()
            ->with('client')
            ->withSum(['payments as total_paid' => fn ($p) => $p->where('status', 'succeeded')], 'amount')
            ->when(!empty($staffClientIds), fn ($qq) => $qq->whereIn('client_id', $staffClientIds))
            ->when($this->clientId, fn ($qq) => $qq->where('client_id', $this->clientId))
            ->when($this->status !== 'all', fn ($qq) => $qq->where('status', $this->status))
            ->when($this->dateFrom, fn ($qq) => $qq->whereDate('issue_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($qq) => $qq->whereDate('issue_date', '<=', $this->dateTo))
            ->latest('issue_date');

        // Payment status filter (approximate, but practical)
        return $q->when($this->paymentStatus !== 'all', function ($qq) {
            return match ($this->paymentStatus) {
                'paid' => $qq->where('status', 'paid'),
                'overdue' => $qq->where('status', 'overdue'),
                'refunded' => $qq->where('status', 'refunded'),
                'unpaid' => $qq->whereIn('status', ['sent', 'overdue'])->whereDoesntHave('payments', fn ($p) => $p->where('status', 'succeeded')),
                'partial' => $qq->whereIn('status', ['sent', 'overdue'])->whereHas('payments', fn ($p) => $p->where('status', 'succeeded')),
                default => $qq,
            };
        });
    }

    protected function paymentsQuery(): Builder
    {
        $user = auth()->user();
        $staffClientIds = [];
        if ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $staffClientIds = $user->assignedClientIds();
        }

        return Payment::query()
            ->with(['invoice', 'client'])
            ->when(!empty($staffClientIds), fn ($q) => $q->whereIn('client_id', $staffClientIds))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->when($this->paymentMethod !== 'all', fn ($q) => $q->where('payment_method', $this->paymentMethod))
            ->when($this->paymentState !== 'all', fn ($q) => $q->where('status', $this->paymentState))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('processed_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('processed_at', '<=', $this->dateTo))
            ->latest('processed_at');
    }

    public function openManualPayment(?int $invoiceId = null): void
    {
        $this->payInvoiceId = $invoiceId;
        $this->payAmount = '';
        $this->payMethod = 'check';
        $this->payTransactionId = '';
        $this->payProcessedAt = null;
        $this->paySendReceipt = true;
        $this->showPaymentModal = true;
    }

    public function saveManualPayment(NotificationService $notifications): void
    {
        $this->validate([
            'payInvoiceId' => ['required', 'integer', 'exists:invoices,id'],
            'payAmount' => ['required', 'numeric', 'min:0.01'],
            'payMethod' => ['required', 'string', 'max:50'],
            'payTransactionId' => ['nullable', 'string', 'max:255'],
            'payProcessedAt' => ['nullable', 'string'],
            'paySendReceipt' => ['boolean'],
        ]);

        $invoice = Invoice::query()->with('client')->findOrFail((int) $this->payInvoiceId);
        $processedAt = $this->payProcessedAt ? \Carbon\Carbon::parse($this->payProcessedAt) : now();

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => (float) $this->payAmount,
            'payment_method' => $this->payMethod,
            'transaction_id' => $this->payTransactionId ?: null,
            'status' => 'succeeded',
            'processed_at' => $processedAt,
            'metadata' => ['recorded_by' => auth()->id(), 'source' => 'manual'],
        ]);

        // Ensure invoice status reflects payment state
        $invoice->refresh();
        if ($invoice->balance_due <= 0) {
            $invoice->markAsPaid();
        } elseif ($invoice->status === 'draft') {
            $invoice->markAsSent();
        }

        ActivityLog::log(
            "Recorded manual payment for invoice: {$invoice->invoice_number}",
            $invoice,
            ['payment_id' => $payment->id, 'amount' => (float) $this->payAmount],
            'created',
            'payments'
        );

        if ($this->paySendReceipt) {
            $notifications->sendPaymentNotification($payment);
        }

        $this->showPaymentModal = false;
        session()->flash('success', 'Payment recorded.');
    }

    public function refundPayment(int $paymentId): void
    {
        $payment = Payment::query()->with('invoice')->findOrFail($paymentId);
        $invoice = $payment->invoice;

        $payment->update([
            'status' => 'refunded',
            'processed_at' => $payment->processed_at ?? now(),
        ]);

        if ($invoice) {
            $invoice->refresh();
            if ($invoice->balance_due > 0 && $invoice->status === 'paid') {
                $invoice->update([
                    'status' => $invoice->due_date && $invoice->due_date->isPast() ? 'overdue' : 'sent',
                    'paid_at' => null,
                ]);
            } elseif ($invoice->balance_due <= 0) {
                $invoice->markAsPaid();
            }

            ActivityLog::log(
                "Refunded payment for invoice: {$invoice->invoice_number}",
                $invoice,
                ['payment_id' => $payment->id],
                'refunded',
                'payments'
            );
        }

        session()->flash('success', 'Payment marked as refunded.');
    }

    public function exportInvoicesCsv()
    {
        $rows = $this->invoicesQuery()->limit(5000)->get();

        $filename = 'invoices-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice #', 'Client', 'Issue Date', 'Due Date', 'Status', 'Amount', 'Paid', 'Balance']);
            foreach ($rows as $inv) {
                fputcsv($out, [
                    $inv->invoice_number,
                    $inv->client?->company_name,
                    optional($inv->issue_date)->format('Y-m-d'),
                    optional($inv->due_date)->format('Y-m-d'),
                    $inv->status,
                    (float) $inv->amount,
                    (float) $inv->total_paid,
                    (float) $inv->balance_due,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportInvoicesPdf()
    {
        $invoices = $this->invoicesQuery()->limit(500)->get();
        $brand = (array) config('client-portal.invoice.branding', []);

        $pdf = Pdf::loadView('admin.invoices.export-list-pdf', compact('invoices', 'brand'));
        return response()->streamDownload(fn () => print($pdf->output()), 'invoices-' . now()->format('Y-m-d_His') . '.pdf');
    }

    public function render()
    {
        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);

        // Summary cards
        $unpaidInvoiceIds = Invoice::query()->whereIn('status', ['sent', 'overdue'])->select('id');
        $unpaidAmount = (float) Invoice::query()->whereIn('status', ['sent', 'overdue'])->sum('amount');
        $unpaidPaid = (float) Payment::query()->where('status', 'succeeded')->whereIn('invoice_id', $unpaidInvoiceIds)->sum('amount');
        $totalOutstanding = max(0, $unpaidAmount - $unpaidPaid);

        $overdueIds = Invoice::query()->where('status', 'overdue')->select('id');
        $overdueAmount = (float) Invoice::query()->where('status', 'overdue')->sum('amount');
        $overduePaid = (float) Payment::query()->where('status', 'succeeded')->whereIn('invoice_id', $overdueIds)->sum('amount');
        $overdueOutstanding = max(0, $overdueAmount - $overduePaid);

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $paidThisMonth = (float) Payment::query()
            ->where('status', 'succeeded')
            ->whereBetween('processed_at', [$start, $end])
            ->sum('amount');

        // Chart: payment method breakdown (this month, succeeded)
        $methodBreakdown = Payment::query()
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->where('status', 'succeeded')
            ->whereBetween('processed_at', [$start, $end])
            ->groupBy('payment_method')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->get()
            ->map(fn ($r) => ['method' => $r->payment_method, 'total' => (float) $r->total])
            ->values()
            ->toArray();

        if ($this->tab === 'payments') {
            return view('livewire.admin.invoices.index', [
                'clients' => $clients,
                'invoiceStatuses' => config('client-portal.invoice_statuses', []),
                'invoices' => null,
                'payments' => $this->paymentsQuery()->paginate(25),
                'totalOutstanding' => $totalOutstanding,
                'paidThisMonth' => $paidThisMonth,
                'overdueAmount' => $overdueOutstanding,
                'methodBreakdown' => $methodBreakdown,
            ])->layout('layouts.admin', ['title' => 'Invoices & Payments']);
        }

        return view('livewire.admin.invoices.index', [
            'clients' => $clients,
            'invoiceStatuses' => config('client-portal.invoice_statuses', []),
            'invoices' => $this->invoicesQuery()->paginate(20),
            'payments' => null,
            'totalOutstanding' => $totalOutstanding,
            'paidThisMonth' => $paidThisMonth,
            'overdueAmount' => $overdueOutstanding,
            'methodBreakdown' => $methodBreakdown,
        ])->layout('layouts.admin', ['title' => 'Invoices & Payments']);
    }
}


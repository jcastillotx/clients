<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Invoices &amp; Payments</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.recurring.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-repeat me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 12v-3a3 3 0 0 1 3 -3h13m-3 -3l3 3l-3 3" /><path d="M20 12v3a3 3 0 0 1 -3 3h-13m3 3l-3 -3l3 -3" /></svg>
                Recurring
            </a>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">Create Invoice</a>
            <button type="button" class="btn btn-outline-secondary" wire:click="openManualPayment()">Record Payment</button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Total Outstanding</div>
                    <div class="h1 mb-0">${{ number_format((float)$totalOutstanding, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Paid This Month</div>
                    <div class="h1 mb-0">${{ number_format((float)$paidThisMonth, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Overdue Amount</div>
                    <div class="h1 mb-0 text-danger">${{ number_format((float)$overdueAmount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab === 'invoices' ? 'active' : '' }}" type="button" wire:click="$set('tab','invoices')">Invoices</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tab === 'payments' ? 'active' : '' }}" type="button" wire:click="$set('tab','payments')">Payments</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label">Client</label>
                    <select class="form-select" wire:model.live="clientId">
                        <option value="">All clients</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>

                @if($tab === 'invoices')
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" wire:model.live="status">
                            <option value="all">All</option>
                            @foreach($invoiceStatuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Payment status</label>
                        <select class="form-select" wire:model.live="paymentStatus">
                            <option value="all">All</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partially paid</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                @else
                    <div class="col-6 col-md-2">
                        <label class="form-label">Method</label>
                        <select class="form-select" wire:model.live="paymentMethod">
                            <option value="all">All</option>
                            <option value="stripe">Stripe</option>
                            <option value="check">Check</option>
                            <option value="wire">Wire</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Payment status</label>
                        <select class="form-select" wire:model.live="paymentState">
                            <option value="all">All</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="succeeded">Succeeded</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($tab === 'payments')
        <div class="card mb-3">
            <div class="card-header">
                <div class="card-title">Payment Method Breakdown (this month)</div>
            </div>
            <div class="card-body">
                <canvas id="adminPaymentMethodChart" height="90"></canvas>
            </div>
        </div>
    @endif

    @if($tab === 'invoices')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">Invoices</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" wire:click="exportInvoicesCsv">Export CSV</button>
                    <button class="btn btn-outline-secondary btn-sm" wire:click="exportInvoicesPdf">Export PDF</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th class="text-end">Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td class="fw-semibold">{{ $inv->invoice_number }}</td>
                            <td>{{ $inv->client?->company_name }}</td>
                            <td class="text-end">${{ number_format((float)$inv->amount, 2) }}</td>
                            <td class="{{ $inv->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ $inv->due_date?->format('Y-m-d') }}</td>
                            <td><span class="badge bg-{{ $inv->status_color }}">{{ $invoiceStatuses[$inv->status] ?? $inv->status }}</span></td>
                            <td class="text-end">${{ number_format((float)$inv->balance_due, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.invoices.edit', $inv) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="openManualPayment({{ $inv->id }})">Record payment</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No invoices found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $invoices->links() }}
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <div class="card-title mb-0">Payments</div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Transaction</th>
                        <th>Processed</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->invoice?->invoice_number ?? ('#' . $p->invoice_id) }}</td>
                            <td>{{ $p->client?->company_name ?? ('Client #' . $p->client_id) }}</td>
                            <td>{{ $p->payment_method }}</td>
                            <td><span class="badge bg-{{ $p->status_color }}">{{ $p->status }}</span></td>
                            <td class="text-muted">{{ $p->transaction_id ?? '—' }}</td>
                            <td class="text-muted">{{ $p->processed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="text-end">${{ number_format((float)$p->amount, 2) }}</td>
                            <td class="text-end">
                                @if($p->status === 'succeeded')
                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="refundPayment({{ $p->id }})">Refund</button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No payments found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $payments->links() }}
            </div>
        </div>
    @endif

    @if($showPaymentModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Manual Payment</h5>
                        <button type="button" class="btn-close" wire:click="$set('showPaymentModal', false)" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Invoice</label>
                                <input type="number" class="form-control" wire:model.live="payInvoiceId" placeholder="Invoice ID">
                                @error('payInvoiceId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                <div class="text-muted small mt-1">Tip: open an invoice row and click “Record payment” to prefill.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" wire:model.live="payAmount" placeholder="0.00">
                                @error('payAmount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Method</label>
                                <select class="form-select" wire:model.live="payMethod">
                                    <option value="check">Check</option>
                                    <option value="wire">Wire</option>
                                    <option value="cash">Cash</option>
                                    <option value="stripe">Stripe</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Processed at</label>
                                <input type="datetime-local" class="form-control" wire:model.live="payProcessedAt">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Transaction ID (optional)</label>
                                <input type="text" class="form-control" wire:model.live="payTransactionId">
                            </div>
                            <div class="col-12">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.live="paySendReceipt">
                                    <span class="form-check-label">Send receipt email to client</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" wire:click="$set('showPaymentModal', false)">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="saveManualPayment" wire:loading.attr="disabled">Save</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @push('scripts')
        <script>
            (function () {
                const data = @json($methodBreakdown);
                function initPaymentChart() {
                    const el = document.getElementById('adminPaymentMethodChart');
                    if (!el || !window.Chart) return;
                    if (window.__adminPaymentChart) {
                        try { window.__adminPaymentChart.destroy(); } catch (e) {}
                        window.__adminPaymentChart = null;
                    }
                    const labels = (data || []).map(x => x.method);
                    const values = (data || []).map(x => x.total);
                    window.__adminPaymentChart = new Chart(el.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{ data: values }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                }
                document.addEventListener('DOMContentLoaded', initPaymentChart);
                document.addEventListener('livewire:initialized', initPaymentChart);
                document.addEventListener('livewire:navigated', initPaymentChart);
            })();
        </script>
    @endpush
</div>


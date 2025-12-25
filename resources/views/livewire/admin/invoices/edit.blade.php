<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Edit {{ $invoice->invoice_number }}</h2>
            <div class="text-muted small">
                {{ $invoice->client?->company_name }} ·
                Status: <span class="badge bg-{{ $invoice->status_color }}">{{ $invoiceStatuses[$invoice->status] ?? $invoice->status }}</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-outline-secondary">Preview PDF</a>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if(!$editable)
        <div class="alert alert-warning">
            This invoice is <strong>{{ $invoiceStatuses[$invoice->status] ?? $invoice->status }}</strong> and is not editable.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0">Invoice</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="sendOrResend" wire:loading.attr="disabled">
                            {{ in_array($invoice->status, ['draft']) ? 'Send to client' : 'Resend email' }}
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" wire:click="voidInvoice" wire:loading.attr="disabled">Void</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Issue date</label>
                            <input type="date" class="form-control" wire:model.live="issue_date" @disabled(!$editable)>
                            @error('issue_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Due date</label>
                            <input type="date" class="form-control" wire:model.live="due_date" @disabled(!$editable)>
                            @error('due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Template</label>
                            <select class="form-select" wire:model.live="template" @disabled(!$editable)>
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('template') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Related request</label>
                            <select class="form-select" wire:model.live="request_id" @disabled(!$editable)>
                                <option value="">None</option>
                                @foreach($requests as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->title }} ({{ $r->status }})</option>
                                @endforeach
                            </select>
                            @error('request_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Related contract</label>
                            <select class="form-select" wire:model.live="contract_id" @disabled(!$editable)>
                                <option value="">None</option>
                                @foreach($contracts as $c)
                                    <option value="{{ $c->id }}">#{{ $c->id }} · {{ $c->title }} ({{ $c->status }})</option>
                                @endforeach
                            </select>
                            @error('contract_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Line items</label>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th style="width:120px;">Qty</th>
                                        <th style="width:160px;">Unit price</th>
                                        <th style="width:160px;" class="text-end">Total</th>
                                        <th style="width:1%;"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($items as $i => $row)
                                        <tr>
                                            <td>
                                                <input type="text" class="form-control" wire:model.live.debounce.250ms="items.{{ $i }}.description" @disabled(!$editable)>
                                                @error("items.$i.description") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" class="form-control" wire:model.live.debounce.250ms="items.{{ $i }}.quantity" @disabled(!$editable)>
                                                @error("items.$i.quantity") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" class="form-control" wire:model.live.debounce.250ms="items.{{ $i }}.unit_price" @disabled(!$editable)>
                                                @error("items.$i.unit_price") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-semibold">${{ number_format((float)($row['total'] ?? 0), 2) }}</div>
                                            </td>
                                            <td class="text-end">
                                                @if($editable)
                                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeItem({{ $i }})">Remove</button>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($editable)
                                <button type="button" class="btn btn-outline-secondary" wire:click="addItem">Add line</button>
                            @endif
                        </div>

                        <div class="col-6 col-lg-3">
                            <label class="form-label">Tax rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" wire:model.live="tax_rate" @disabled(!$editable)>
                            @error('tax_rate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label">Discount</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model.live="discount" @disabled(!$editable)>
                            @error('discount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="alert alert-info mb-0">
                                <div><strong>Total:</strong> ${{ number_format((float)$invoice->amount, 2) }}</div>
                                <div><strong>Paid:</strong> ${{ number_format((float)$invoice->total_paid, 2) }}</div>
                                <div><strong>Balance due:</strong> ${{ number_format((float)$invoice->balance_due, 2) }}</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" rows="3" wire:model.live.debounce.350ms="notes" @disabled(!$editable)></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Terms</label>
                            <textarea class="form-control" rows="3" wire:model.live.debounce.350ms="terms" @disabled(!$editable)></textarea>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" @disabled(!$editable)>Save changes</button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="openPaymentModal" wire:loading.attr="disabled">Record payment</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Payments</div>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($invoice->payments as $p)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div class="fw-semibold">${{ number_format((float)$p->amount, 2) }}</div>
                                    <span class="badge bg-{{ $p->status_color }}">{{ $p->status }}</span>
                                </div>
                                <div class="text-muted small">{{ $p->payment_method }} · {{ $p->transaction_id ?? '—' }}</div>
                                <div class="text-muted small">{{ $p->processed_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            </div>
                        @empty
                            <div class="text-muted">No payments recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" wire:model.live="payAmount">
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
                            <div class="col-12 col-md-6">
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
                        <button type="button" class="btn btn-primary" wire:click="savePayment" wire:loading.attr="disabled">Save</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>


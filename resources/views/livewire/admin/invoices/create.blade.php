<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Create Invoice</h2>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="row g-3">
        {{-- Main Form Column --}}
        <div class="col-12 col-xl-8">
            {{-- Client & Template Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Client & Template</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Client <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="client_id">
                                <option value="">Select a client…</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Template</label>
                            <select class="form-select" wire:model.live="template">
                                @foreach($templates as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('template') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Invoice Details Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Invoice Details</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Invoice Number</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <label class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" wire:model.live="autoNumber">
                                        <span class="form-check-label">Auto</span>
                                    </label>
                                </span>
                                <input type="text" class="form-control" wire:model.live="invoice_number" placeholder="INV-YYYYMM-0001" @disabled($autoNumber)>
                            </div>
                            @error('invoice_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            <div class="form-hint">If Auto is enabled, the number is generated on save.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" wire:model.live="issue_date">
                            @error('issue_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" wire:model.live="due_date">
                            @error('due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Related Links Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Related Links</h3>
                    <p class="card-subtitle">Optional: Link this invoice to a request or contract</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Related Request</label>
                            <select class="form-select" wire:model.live="request_id" @disabled(!$client_id)>
                                <option value="">None</option>
                                @foreach($requests as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->title }} ({{ $r->status }})</option>
                                @endforeach
                            </select>
                            @error('request_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Related Contract</label>
                            <select class="form-select" wire:model.live="contract_id" @disabled(!$client_id)>
                                <option value="">None</option>
                                @foreach($contracts as $c)
                                    <option value="{{ $c->id }}">#{{ $c->id }} · {{ $c->title }} ({{ $c->status }})</option>
                                @endforeach
                            </select>
                            @error('contract_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Line Items Card --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Line Items</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addItem">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        Add Line
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">Description</th>
                                <th style="width: 180px;">Service / Feature</th>
                                <th style="width: 100px;">Qty</th>
                                <th style="width: 120px;">Unit Price</th>
                                <th style="width: 120px;" class="text-end">Total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $i => $row)
                                <tr>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" wire:model.live.debounce.250ms="items.{{ $i }}.description" placeholder="Design work, monthly retainer, …">
                                        @error("items.$i.description") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model.live="items.{{ $i }}.feature_key" title="If set, paying this invoice enables this feature for the client">
                                            <option value="">None</option>
                                            @foreach($featureOptions as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("items.$i.feature_key") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end" wire:model.live.debounce.250ms="items.{{ $i }}.quantity">
                                        @error("items.$i.quantity") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-end" wire:model.live.debounce.250ms="items.{{ $i }}.unit_price">
                                        </div>
                                        @error("items.$i.unit_price") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">${{ number_format((float)($row['total'] ?? 0), 2) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-ghost-danger btn-icon btn-sm" wire:click="removeItem({{ $i }})" title="Remove line">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No line items yet. Click "Add Line" to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(count($featureOptions) > 0)
                    <div class="card-footer bg-transparent border-top-0">
                        <div class="text-muted small">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                            Selecting a Service/Feature will enable that feature for the client when the invoice is paid.
                        </div>
                    </div>
                @endif
            </div>

            {{-- Notes & Terms Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Notes & Terms</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" rows="4" wire:model.live.debounce.350ms="notes" placeholder="Additional notes for the client…"></textarea>
                            @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Terms</label>
                            <textarea class="form-control" rows="4" wire:model.live.debounce.350ms="terms" placeholder="Payment terms and conditions…"></textarea>
                            @error('terms') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Column --}}
        <div class="col-12 col-xl-4">
            {{-- Totals Card --}}
            <div class="card mb-3 sticky-top" style="top: 1rem; z-index: 100;">
                <div class="card-header">
                    <h3 class="card-title mb-0">Summary</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" wire:model.live="tax_rate">
                            @error('tax_rate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Discount ($)</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model.live="discount">
                            @error('discount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-medium">${{ number_format((float)$subtotal, 2) }}</span>
                        </div>
                        @if((float)$discount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Discount</span>
                                <span class="fw-medium text-danger">-${{ number_format((float)$discount, 2) }}</span>
                            </div>
                        @endif
                        @if((float)$tax_rate > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tax ({{ number_format((float)$tax_rate, 2) }}%)</span>
                                <span class="fw-medium">${{ number_format((float)$taxAmount, 2) }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="fw-bold">Total</span>
                            <span class="h3 mb-0 text-primary">${{ number_format((float)$total, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" wire:click="sendToClient" wire:loading.attr="disabled">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-send" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>
                            <span wire:loading.remove wire:target="sendToClient">Send to Client</span>
                            <span wire:loading wire:target="sendToClient">Sending…</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="saveDraft" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveDraft">Save as Draft</span>
                            <span wire:loading wire:target="saveDraft">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

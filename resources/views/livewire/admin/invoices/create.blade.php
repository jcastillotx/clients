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
            {{-- Invoice Type Toggle --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="invoice_type" id="type_onetime" value="0" wire:model.live="is_recurring" {{ !$is_recurring ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="type_onetime">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-invoice me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 7l1 0" /><path d="M9 13l6 0" /><path d="M13 17l2 0" /></svg>
                            One-Time Invoice
                        </label>
                        <input type="radio" class="btn-check" name="invoice_type" id="type_recurring" value="1" wire:model.live="is_recurring" {{ $is_recurring ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="type_recurring">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-repeat me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 12v-3a3 3 0 0 1 3 -3h13m-3 -3l3 3l-3 3" /><path d="M20 12v3a3 3 0 0 1 -3 3h-13m3 3l-3 -3l3 -3" /></svg>
                            Recurring Invoice
                        </label>
                    </div>
                </div>
            </div>

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

            {{-- Recurring Schedule Card (only shown when recurring) --}}
            @if($is_recurring)
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-primary-lt">
                        <h3 class="card-title mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-repeat me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v3" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h12" /><path d="M20 14l2 2h-3" /><path d="M20 18l2 -2" /><path d="M19 16a3 3 0 1 0 2 5.236" /></svg>
                            Recurring Schedule
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Schedule Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.live="recurring_name" placeholder="e.g., Monthly Retainer - Acme Corp">
                                @error('recurring_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                <div class="form-hint">Internal name to identify this recurring invoice schedule.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Frequency <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model.live="recurring_frequency">
                                    @foreach($frequencyOptions as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('recurring_frequency') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            @if(in_array($recurring_frequency, ['monthly', 'quarterly', 'yearly']))
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Day of Month</label>
                                    <select class="form-select" wire:model.live="recurring_day_of_month">
                                        <option value="">Same as start date</option>
                                        @for($d = 1; $d <= 28; $d++)
                                            <option value="{{ $d }}">{{ $d }}{{ $d == 1 ? 'st' : ($d == 2 ? 'nd' : ($d == 3 ? 'rd' : 'th')) }}</option>
                                        @endfor
                                    </select>
                                    @error('recurring_day_of_month') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    <div class="form-hint">Generate invoice on this day each period (max 28 to handle all months).</div>
                                </div>
                            @endif

                            @if(in_array($recurring_frequency, ['weekly', 'biweekly']))
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Day of Week</label>
                                    <select class="form-select" wire:model.live="recurring_day_of_week">
                                        <option value="">Same as start date</option>
                                        @foreach($dayOfWeekOptions as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('recurring_day_of_week') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <div class="col-12 col-md-4">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" wire:model.live="recurring_start_date">
                                @error('recurring_start_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                <div class="form-hint">First invoice generated on this date.</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" wire:model.live="recurring_end_date">
                                @error('recurring_end_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                <div class="form-hint">Leave empty for indefinite.</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Max Occurrences</label>
                                <input type="number" class="form-control" wire:model.live="recurring_occurrences_limit" min="1" max="999" placeholder="Unlimited">
                                @error('recurring_occurrences_limit') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                <div class="form-hint">Stop after this many invoices.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Payment Terms (Days)</label>
                                <input type="number" class="form-control" wire:model.live="recurring_payment_terms_days" min="0" max="365">
                                @error('recurring_payment_terms_days') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                <div class="form-hint">Due date = Issue date + this many days.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">&nbsp;</label>
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.live="recurring_auto_send">
                                    <span class="form-check-label">Auto-send to client when generated</span>
                                </label>
                                <div class="form-hint">If unchecked, invoices are created as drafts.</div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Invoice Details Card (only for one-time) --}}
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
            @endif

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
                            <span class="fw-bold">{{ $is_recurring ? 'Per Invoice' : 'Total' }}</span>
                            <span class="h3 mb-0 text-primary">${{ number_format((float)$total, 2) }}</span>
                        </div>
                    </div>

                    @if($is_recurring)
                        <div class="alert alert-info mt-3 mb-0">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                                </div>
                                <div class="ms-2">
                                    <h4 class="alert-title mb-1">Recurring Invoice</h4>
                                    <div class="text-muted small">
                                        Invoices will be automatically generated {{ strtolower($frequencyOptions[$recurring_frequency] ?? $recurring_frequency) }}
                                        @if($recurring_start_date)
                                            starting {{ \Carbon\Carbon::parse($recurring_start_date)->format('M j, Y') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2">
                        @if($is_recurring)
                            <button type="button" class="btn btn-primary" wire:click="saveRecurring" wire:loading.attr="disabled">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-plus me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v5" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M16 19h6" /><path d="M19 16v6" /></svg>
                                <span wire:loading.remove wire:target="saveRecurring">Create Recurring Schedule</span>
                                <span wire:loading wire:target="saveRecurring">Creating…</span>
                            </button>
                        @else
                            <button type="button" class="btn btn-primary" wire:click="sendToClient" wire:loading.attr="disabled">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-send me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>
                                <span wire:loading.remove wire:target="sendToClient">Send to Client</span>
                                <span wire:loading wire:target="sendToClient">Sending…</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="saveDraft" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveDraft">Save as Draft</span>
                                <span wire:loading wire:target="saveDraft">Saving…</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

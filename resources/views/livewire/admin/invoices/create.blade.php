<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Create Invoice</h2>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Client</label>
                    <select class="form-select" wire:model.live="client_id">
                        <option value="">Select a client…</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Template</label>
                    <select class="form-select" wire:model.live="template">
                        @foreach($templates as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('template') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="hr-text">Invoice details</div>
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label">Invoice number</label>
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
                    <div class="text-muted small mt-1">If Auto is enabled, the number is generated on save.</div>
                </div>

                <div class="col-6 col-lg-4">
                    <label class="form-label">Issue date</label>
                    <input type="date" class="form-control" wire:model.live="issue_date">
                    @error('issue_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-lg-4">
                    <label class="form-label">Due date</label>
                    <input type="date" class="form-control" wire:model.live="due_date">
                    @error('due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="hr-text">Links (optional)</div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Related request</label>
                    <select class="form-select" wire:model.live="request_id" @disabled(!$client_id)>
                        <option value="">None</option>
                        @foreach($requests as $r)
                            <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->title }} ({{ $r->status }})</option>
                        @endforeach
                    </select>
                    @error('request_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Related contract</label>
                    <select class="form-select" wire:model.live="contract_id" @disabled(!$client_id)>
                        <option value="">None</option>
                        @foreach($contracts as $c)
                            <option value="{{ $c->id }}">#{{ $c->id }} · {{ $c->title }} ({{ $c->status }})</option>
                        @endforeach
                    </select>
                    @error('contract_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="hr-text">Line items</div>
                </div>

                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                            <tr>
                                <th>Description</th>
                                <th style="width:220px;">Service / Feature</th>
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
                                        <input type="text" class="form-control" wire:model.live.debounce.250ms="items.{{ $i }}.description" placeholder="Design work, monthly retainer, …">
                                        @error("items.$i.description") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <select class="form-select" wire:model.live="items.{{ $i }}.feature_key">
                                            <option value="">None</option>
                                            @foreach($featureOptions as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="text-muted small mt-1">If set, paying this invoice will enable the feature for the client.</div>
                                        @error("items.$i.feature_key") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" class="form-control" wire:model.live.debounce.250ms="items.{{ $i }}.quantity">
                                        @error("items.$i.quantity") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control" wire:model.live.debounce.250ms="items.{{ $i }}.unit_price">
                                        @error("items.$i.unit_price") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-semibold">${{ number_format((float)($row['total'] ?? 0), 2) }}</div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeItem({{ $i }})">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-secondary" wire:click="addItem">Add line</button>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="hr-text">Notes / Terms</div>
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" rows="4" wire:model.live.debounce.350ms="notes"></textarea>
                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <label class="form-label mt-3">Terms</label>
                    <textarea class="form-control" rows="4" wire:model.live.debounce.350ms="terms"></textarea>
                    @error('terms') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <div class="hr-text">Totals</div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Tax rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" wire:model.live="tax_rate">
                            @error('tax_rate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Discount</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model.live="discount">
                            @error('discount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <div class="text-muted">Subtotal</div>
                            <div class="fw-semibold">${{ number_format((float)$subtotal, 2) }}</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="text-muted">Tax</div>
                            <div class="fw-semibold">${{ number_format((float)$taxAmount, 2) }}</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="text-muted">Total</div>
                            <div class="h2 mb-0">${{ number_format((float)$total, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-outline-secondary" wire:click="saveDraft" wire:loading.attr="disabled">Save as Draft</button>
                    <button type="button" class="btn btn-primary" wire:click="sendToClient" wire:loading.attr="disabled">Send to Client</button>
                </div>
            </div>
        </div>
    </div>
</div>


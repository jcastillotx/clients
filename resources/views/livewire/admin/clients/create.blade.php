<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <div class="h2 mb-0">Add New Client</div>
                <div class="text-muted">Creates the client record and a client user account.</div>
            </div>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>

        <form wire:submit.prevent="save" class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Company Name *</label>
                        <input wire:model.live.debounce.300ms="company_name" type="text" class="form-control">
                        @error('company_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Contact Name *</label>
                        <input wire:model.live.debounce.300ms="contact_name" type="text" class="form-control">
                        @error('contact_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Email *</label>
                        <input wire:model.live.debounce.300ms="email" type="email" class="form-control">
                        @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-hint">This email will be used to create the client user account.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Phone</label>
                        <input wire:model.live.debounce.300ms="phone" type="text" class="form-control">
                        @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tier</label>
                        <select wire:model.live="tier" class="form-select">
                            @foreach($tiers as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('tier') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status</label>
                        <select wire:model.live="status" class="form-select">
                            @foreach($statuses as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                        <div class="fw-semibold">Address</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Street</label>
                        <input wire:model.live.debounce.300ms="address" type="text" class="form-control">
                        @error('address') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">City</label>
                        <input wire:model.live.debounce.300ms="city" type="text" class="form-control">
                        @error('city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">State</label>
                        <input wire:model.live.debounce.300ms="state" type="text" class="form-control">
                        @error('state') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">ZIP</label>
                        <input wire:model.live.debounce.300ms="zip_code" type="text" class="form-control">
                        @error('zip_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                        <div class="fw-semibold">Billing (optional)</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Stripe Customer ID</label>
                        <input wire:model.live.debounce.300ms="stripe_customer_id" type="text" class="form-control" placeholder="cus_...">
                        @error('stripe_customer_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Internal Notes</label>
                        <textarea wire:model.live.debounce.400ms="notes" class="form-control" rows="3"></textarea>
                        @error('notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-hint">Not visible to the client.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.live="sendPasswordSetLink">
                            <span class="form-check-label">Send password set link (recommended)</span>
                        </label>
                        <div class="form-hint">If disabled, a temporary password will be emailed.</div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Create Client</span>
                    <span wire:loading wire:target="save">Creating…</span>
                </button>
            </div>
        </form>
    </div>
</div>


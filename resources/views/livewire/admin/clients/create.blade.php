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

                    <div class="col-12">
                        <hr class="my-2">
                        <div class="fw-semibold mb-2">Services & Features</div>
                        <div class="form-hint mb-3">Select which services this client has access to. These are in addition to tier-based features.</div>
                        
                        @php
                            $categories = [
                                'core' => 'Core Features',
                                'brand_monitoring' => 'Brand Monitoring',
                                'ai' => 'AI Features',
                                'advanced' => 'Advanced Features',
                                'collaboration' => 'Collaboration',
                                'research' => 'Research & Consultation',
                            ];
                        @endphp

                        <div class="row g-3">
                            @foreach($categories as $categoryKey => $categoryLabel)
                                @if(isset($servicesByCategory[$categoryKey]))
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card">
                                            <div class="card-header py-2">
                                                <h4 class="card-title mb-0">{{ $categoryLabel }}</h4>
                                            </div>
                                            <div class="card-body py-2">
                                                @foreach($servicesByCategory[$categoryKey] as $serviceKey => $service)
                                                    @php
                                                        $tierIncludes = in_array($serviceKey, $tierFeatures[$tier] ?? []);
                                                    @endphp
                                                    <label class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                            wire:model.live="selectedServices" 
                                                            value="{{ $serviceKey }}"
                                                            @if($tierIncludes) checked disabled title="Included in {{ ucfirst($tier) }} tier" @endif>
                                                        <span class="form-check-label">
                                                            {{ $service['name'] }}
                                                            @if($tierIncludes)
                                                                <span class="badge bg-info ms-1" title="Included in tier">Tier</span>
                                                            @endif
                                                        </span>
                                                        <div class="text-muted small">{{ $service['description'] }}</div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @error('selectedServices') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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


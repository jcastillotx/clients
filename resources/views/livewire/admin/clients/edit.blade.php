<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <div>
                <div class="h2 mb-0">Edit Client</div>
                <div class="text-muted">{{ $client->company_name }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary">Back</a>
                <button type="button" class="btn btn-outline-secondary" wire:click="sendPasswordReset">Send password reset</button>
                <button type="button" class="btn btn-outline-warning" wire:click="openPasswordModal">Set password</button>
            </div>
        </div>

        <ul class="nav nav-tabs">
            <li class="nav-item">
                <button class="nav-link @if($tab==='overview') active @endif" wire:click="$set('tab','overview')" type="button">Overview</button>
            </li>
            <li class="nav-item">
                <button class="nav-link @if($tab==='services') active @endif" wire:click="$set('tab','services')" type="button">Services</button>
            </li>
            <li class="nav-item">
                <button class="nav-link @if($tab==='activity') active @endif" wire:click="$set('tab','activity')" type="button">Activity history</button>
            </li>
        </ul>

        <div class="card border-top-0 rounded-top-0">
            <div class="card-body">
                @if($tab === 'overview')
                    <form wire:submit.prevent="save">
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
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">City</label>
                                <input wire:model.live.debounce.300ms="city" type="text" class="form-control">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">State</label>
                                <input wire:model.live.debounce.300ms="state" type="text" class="form-control">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">ZIP</label>
                                <input wire:model.live.debounce.300ms="zip_code" type="text" class="form-control">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Stripe Customer ID</label>
                                <input wire:model.live.debounce.300ms="stripe_customer_id" type="text" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Internal Notes</label>
                                <textarea wire:model.live.debounce.400ms="notes" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info">
                                    <div class="fw-semibold">Linked user account</div>
                                    <div class="text-muted small">
                                        {{ $primaryUser?->email ?? 'No user linked yet' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">Save changes</span>
                                <span wire:loading wire:target="save">Saving…</span>
                            </button>
                        </div>
                    </form>
                @endif

                @if($tab === 'services')
                    <div class="mb-3">
                        <div class="alert alert-info">
                            <strong>Tier Features:</strong> The client's tier ({{ ucfirst($tier) }}) includes certain features by default. 
                            Additional services checked below will be added on top of tier features.
                        </div>
                    </div>

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
                                    <div class="card h-100">
                                        <div class="card-header py-2">
                                            <h4 class="card-title mb-0">{{ $categoryLabel }}</h4>
                                        </div>
                                        <div class="card-body py-2">
                                            @foreach($servicesByCategory[$categoryKey] as $serviceKey => $service)
                                                @php
                                                    $tierIncludes = in_array($serviceKey, $tierFeatures[$tier] ?? []);
                                                    $isSelected = in_array($serviceKey, $selectedServices);
                                                @endphp
                                                <label class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                        wire:model.live="selectedServices" 
                                                        value="{{ $serviceKey }}"
                                                        @if($tierIncludes) checked disabled @endif>
                                                    <span class="form-check-label">
                                                        {{ $service['name'] }}
                                                        @if($tierIncludes)
                                                            <span class="badge bg-info ms-1" title="Included in {{ ucfirst($tier) }} tier">Tier</span>
                                                        @elseif($isSelected)
                                                            <span class="badge bg-success ms-1">Added</span>
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

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" wire:click="saveServices" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveServices">Save Services</span>
                            <span wire:loading wire:target="saveServices">Saving…</span>
                        </button>
                    </div>
                @endif

                @if($tab === 'activity')
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                            <tr>
                                <th>When</th>
                                <th>User</th>
                                <th>Log</th>
                                <th>Description</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($activities as $a)
                                <tr>
                                    <td class="text-muted">{{ $a->created_at?->diffForHumans() }}</td>
                                    <td>{{ $a->user?->name ?? 'System' }}</td>
                                    <td><span class="badge bg-secondary">{{ $a->log_name }}</span></td>
                                    <td>{{ $a->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No activity yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $activities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Set Password Modal --}}
    @if($showPasswordModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Set Password</h5>
                        <button type="button" class="btn-close" wire:click="closePasswordModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control @error('newPassword') is-invalid @enderror" wire:model="newPassword" autocomplete="new-password">
                            @error('newPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control @error('newPasswordConfirmation') is-invalid @enderror" wire:model="newPasswordConfirmation" autocomplete="new-password">
                            @error('newPasswordConfirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="text-muted small">Password must be at least 8 characters.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closePasswordModal">Cancel</button>
                        <button type="button" class="btn btn-warning" wire:click="setPassword" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="setPassword">Set Password</span>
                            <span wire:loading wire:target="setPassword">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


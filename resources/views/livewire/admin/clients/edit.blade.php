<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <div>
                <div class="h2 mb-0">Edit Client</div>
                <div class="text-muted">{{ $client->company_name }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary">Back</a>
                <button type="button" class="btn btn-outline-warning" wire:click="sendPasswordReset">Send password reset</button>
            </div>
        </div>

        <ul class="nav nav-tabs">
            <li class="nav-item">
                <button class="nav-link @if($tab==='overview') active @endif" wire:click="$set('tab','overview')" type="button">Overview</button>
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
</div>


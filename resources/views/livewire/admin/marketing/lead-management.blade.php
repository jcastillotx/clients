<div class="space-y-3">
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div>
            <div class="h2 mb-0">Leads</div>
            <div class="text-muted">Capture new leads and track outreach progress.</div>
        </div>
    </div>

    @include('partials.flash-messages')

    <div class="card">
        <div class="card-header">
            <strong>New Lead</strong>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="createLead">
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Name</label>
                        <input wire:model.defer="name" type="text" class="form-control" placeholder="Lead name">
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Email</label>
                        <input wire:model.defer="email" type="email" class="form-control" placeholder="name@example.com">
                        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Phone</label>
                        <input wire:model.defer="phone" type="text" class="form-control" placeholder="(555) 555-5555">
                        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Company</label>
                        <input wire:model.defer="company" type="text" class="form-control" placeholder="Company name">
                        @error('company') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Source</label>
                        <input wire:model.defer="source" type="text" class="form-control" placeholder="Referral, event, web">
                        @error('source') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Assigned To</label>
                        <select wire:model.defer="assignedTo" class="form-select">
                            <option value="">Unassigned</option>
                            @foreach($assignees as $assignee)
                                <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                        @error('assignedTo') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Status</label>
                        <select wire:model.defer="status" class="form-select">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Score</label>
                        <input wire:model.defer="score" type="number" min="0" max="100" class="form-control" placeholder="0 - 100">
                        @error('score') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-lg-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Add Lead</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label d-block">Search</label>
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Name, email, company, source…">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label d-block">Status</label>
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="all">All statuses</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th class="text-end">Score</th>
                        <th>Assigned</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td class="fw-semibold">{{ $lead->name }}</td>
                            <td>{{ $lead->email ?: '—' }}</td>
                            <td>{{ $lead->company ?: '—' }}</td>
                            <td>{{ $lead->source ?: '—' }}</td>
                            <td>
                                <span class="badge bg-secondary text-uppercase">{{ $statusOptions[$lead->status] ?? ucfirst($lead->status) }}</span>
                            </td>
                            <td class="text-end">{{ $lead->score ?? '—' }}</td>
                            <td>{{ $lead->assignee?->name ?? 'Unassigned' }}</td>
                            <td>{{ $lead->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-muted p-4">No leads found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $leads->links() }}
        </div>
    </div>
</div>

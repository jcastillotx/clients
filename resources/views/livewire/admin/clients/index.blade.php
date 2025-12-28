<div class="space-y-3">
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div>
            <div class="h2 mb-0">Clients</div>
            <div class="text-muted">Manage client accounts, status, and revenue.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary" wire:click="exportCsv">Export CSV</button>
            <button type="button" class="btn btn-outline-secondary" wire:click="exportPdf">Export PDF</button>
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">Add New Client</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label">Search</label>
                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Company, contact, email…">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select wire:model.live="status" class="form-select">
                        @foreach($statuses as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Tier</label>
                    <select wire:model.live="tier" class="form-select">
                        @foreach($tiers as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">From</label>
                    <input wire:model.live="dateFrom" type="date" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">To</label>
                    <input wire:model.live="dateTo" type="date" class="form-control">
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.live="selectPage">
                    <span class="form-check-label">Select first 50 results</span>
                </label>

                @if(count($selected) > 0)
                    <span class="badge bg-primary ms-2">{{ count($selected) }} selected</span>
                @endif

                <div class="ms-auto d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-success" type="button" wire:click="bulkActivate" @disabled(empty($selected))>
                        Activate
                    </button>
                    <button class="btn btn-outline-warning" type="button" wire:click="bulkSuspend" @disabled(empty($selected))>
                        Suspend
                    </button>
                    <button class="btn btn-outline-secondary" type="button" wire:click="confirmBulkArchive" @disabled(empty($selected))>
                        Archive
                    </button>
                    <button class="btn btn-outline-danger" type="button" wire:click="confirmBulkDelete" @disabled(empty($selected))>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th style="width: 1%;">
                            <span class="text-muted">Sel</span>
                        </th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Tier</th>
                        <th>Status</th>
                        <th class="text-end">Active Requests</th>
                        <th class="text-end">Total Revenue</th>
                        <th>Last Activity</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr style="cursor:pointer" onclick="window.location='{{ route('admin.clients.show', $client) }}'">
                            <td onclick="event.stopPropagation()">
                                <input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $client->id }}">
                            </td>
                            <td class="fw-semibold">{{ $client->company_name }}</td>
                            <td>{{ $client->contact_name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ ucfirst($client->tier) }}</td>
                            <td>
                                <span class="badge bg-{{ $client->status === 'active' ? 'success' : ($client->status === 'suspended' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>
                            <td class="text-end">{{ (int) ($client->active_requests_count ?? 0) }}</td>
                            <td class="text-end">${{ number_format((float) ($client->total_revenue ?? 0), 2) }}</td>
                            <td>
                                @if($client->last_activity_at)
                                    {{ \Illuminate\Support\Carbon::parse($client->last_activity_at)->diffForHumans() }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end" onclick="event.stopPropagation()">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.clients.edit', $client) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted p-4">No clients found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $clients->links() }}
        </div>
    </div>

    {{-- Bulk Archive Confirmation Modal --}}
    @if($showArchiveConfirmModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title">Confirm Archive</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="cancelBulkArchive"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to archive <strong>{{ count($selected) }}</strong> selected client(s)?</p>
                        <p class="text-muted mb-0">
                            <small>Archived clients will be hidden from the list but can be restored later if needed.</small>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelBulkArchive">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="bulkArchive">Archive Clients</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Delete Confirmation Modal --}}
    @if($showDeleteConfirmModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirm Permanent Delete</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="cancelBulkDelete"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to <strong>permanently delete</strong> <strong>{{ count($selected) }}</strong> selected client(s)?</p>
                        <p class="text-danger mb-0">
                            <strong>Warning:</strong> This action cannot be undone. All associated data (requests, invoices, contracts, etc.) may also be affected.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelBulkDelete">Cancel</button>
                        <button type="button" class="btn btn-danger" wire:click="bulkDelete">Delete Permanently</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


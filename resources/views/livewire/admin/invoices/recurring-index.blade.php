<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Recurring Invoices</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">All Invoices</a>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                New Recurring
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search by name or client…">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="status">
                        <option value="">All Statuses</option>
                        @foreach($statusOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Frequency</label>
                    <select class="form-select" wire:model.live="frequency">
                        <option value="">All Frequencies</option>
                        @foreach($frequencyOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Recurring Invoices Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('name')" class="text-reset text-decoration-none">
                                Name
                                @if($sortField === 'name')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        @if($sortDirection === 'asc')
                                            <path d="M6 15l6 -6l6 6" />
                                        @else
                                            <path d="M6 9l6 6l6 -6" />
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th>Client</th>
                        <th>Frequency</th>
                        <th class="text-end">Amount</th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('next_generate_date')" class="text-reset text-decoration-none">
                                Next Invoice
                                @if($sortField === 'next_generate_date')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        @if($sortDirection === 'asc')
                                            <path d="M6 15l6 -6l6 6" />
                                        @else
                                            <path d="M6 9l6 6l6 -6" />
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="text-center">Generated</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recurringInvoices as $recurring)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $recurring->name }}</div>
                                @if($recurring->auto_send)
                                    <div class="text-muted small">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-send icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>
                                        Auto-send enabled
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($recurring->client)
                                    <a href="{{ route('admin.clients.edit', $recurring->client) }}" class="text-reset">
                                        {{ $recurring->client->company_name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-azure-lt">{{ $recurring->frequency_label }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold">${{ number_format($recurring->total, 2) }}</span>
                            </td>
                            <td>
                                @if($recurring->next_generate_date && $recurring->status === 'active')
                                    <div>{{ $recurring->next_generate_date->format('M j, Y') }}</div>
                                    <div class="text-muted small">
                                        @if($recurring->next_generate_date->isToday())
                                            Today
                                        @elseif($recurring->next_generate_date->isTomorrow())
                                            Tomorrow
                                        @elseif($recurring->next_generate_date->isPast())
                                            <span class="text-warning">Pending generation</span>
                                        @else
                                            {{ $recurring->next_generate_date->diffForHumans() }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-lt">{{ $recurring->invoices_count }}</span>
                                @if($recurring->occurrences_limit)
                                    <span class="text-muted small">/ {{ $recurring->occurrences_limit }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $recurring->status_color }}">
                                    {{ $statusOptions[$recurring->status] ?? ucfirst($recurring->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @if($recurring->status === 'active')
                                            <button class="dropdown-item" wire:click="pause({{ $recurring->id }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-player-pause me-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" /><path d="M14 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" /></svg>
                                                Pause
                                            </button>
                                        @endif
                                        @if($recurring->status === 'paused')
                                            <button class="dropdown-item" wire:click="resume({{ $recurring->id }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-player-play me-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 4v16l13 -8z" /></svg>
                                                Resume
                                            </button>
                                        @endif
                                        @if(in_array($recurring->status, ['active', 'paused']))
                                            <button class="dropdown-item text-warning" wire:click="cancel({{ $recurring->id }})" onclick="return confirm('Are you sure you want to cancel this recurring invoice?')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-ban me-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M5.7 5.7l12.6 12.6" /></svg>
                                                Cancel
                                            </button>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item text-danger" wire:click="delete({{ $recurring->id }})" onclick="return confirm('Are you sure you want to delete this recurring invoice? This cannot be undone.')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash me-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-repeat mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v3" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h12" /><path d="M20 14l2 2h-3" /><path d="M20 18l2 -2" /><path d="M19 16a3 3 0 1 0 2 5.236" /></svg>
                                    <div>No recurring invoices found.</div>
                                    <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary mt-2">Create Your First Recurring Invoice</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recurringInvoices->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $recurringInvoices->links() }}
            </div>
        @endif
    </div>
</div>

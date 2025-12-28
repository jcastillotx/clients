<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <div class="h2 mb-0">{{ $client->company_name }}</div>
            <div class="text-muted">{{ $client->contact_name }} · {{ $client->email }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-outline-secondary">Edit</a>
            <button type="button" class="btn btn-outline-secondary" wire:click="exportCsv">Export CSV</button>
            <button type="button" class="btn btn-outline-secondary" wire:click="exportPdf">Export PDF</button>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row row-cards mb-3">
    <div class="col-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="subheader">Active Requests</div>
                <div class="h1 mb-0">{{ $stats['active_requests'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="subheader">Pending Invoices</div>
                <div class="h1 mb-0">{{ $stats['pending_invoices'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="subheader">Active Contracts</div>
                <div class="h1 mb-0">{{ $stats['active_contracts'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="subheader">Total Revenue</div>
                <div class="h1 mb-0">${{ number_format((float) $stats['revenue'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs">
    @php
        $tabs = [
            'overview' => 'Overview',
            'requests' => 'Requests',
            'invoices' => 'Invoices',
            'contracts' => 'Contracts',
            'documents' => 'Documents',
            'storage' => 'Storage',
            'activity' => 'Activity Log',
            'notes' => 'Notes',
        ];
    @endphp
    @foreach($tabs as $key => $label)
        <li class="nav-item">
            <button type="button" class="nav-link @if($tab===$key) active @endif" wire:click="$set('tab','{{ $key }}')">
                {{ $label }}
            </button>
        </li>
    @endforeach
</ul>

<div class="card border-top-0 rounded-top-0">
    <div class="card-body">
        @if($tab === 'overview')
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Contact & Company</div>
                            <div class="text-muted small">Tier: <strong>{{ ucfirst($client->tier) }}</strong> · Status: <strong>{{ ucfirst($client->status) }}</strong></div>
                            <div class="mt-2">
                                <div><strong>Contact:</strong> {{ $client->contact_name }}</div>
                                <div><strong>Email:</strong> {{ $client->email }}</div>
                                <div><strong>Phone:</strong> {{ $client->phone ?? '—' }}</div>
                                <div><strong>Address:</strong> {{ $client->full_address ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Recent activity</div>
                            <div class="list-group list-group-flush">
                                @forelse($recentActivity as $a)
                                    <div class="list-group-item px-0">
                                        <div class="fw-semibold">{{ $a->description }}</div>
                                        <div class="text-muted small">{{ $a->created_at?->diffForHumans() }} · {{ $a->user?->name ?? 'System' }}</div>
                                    </div>
                                @empty
                                    <div class="text-muted">No recent activity.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($tab === 'requests')
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td>#{{ $r->id }}</td>
                            <td class="fw-semibold">{{ $r->title }}</td>
                            <td style="min-width: 220px;">
                                <select class="form-select form-select-sm" wire:change="updateRequestStatus({{ $r->id }}, $event.target.value)">
                                    @foreach($requestStatuses as $k => $label)
                                        <option value="{{ $k }}" @selected($r->status === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>{{ $r->priority_label }}</td>
                            <td class="text-muted">{{ $r->created_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No requests.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $requests->links() }}</div>
        @endif

        @if($tab === 'invoices')
            <div class="d-flex justify-content-end mb-2">
                <a href="{{ route('admin.invoices.create') }}" class="btn btn-outline-primary">Create Invoice</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td class="fw-semibold">{{ $inv->invoice_number }}</td>
                            <td><span class="badge bg-secondary">{{ $inv->status_label }}</span></td>
                            <td class="text-end">${{ number_format((float) $inv->amount, 2) }}</td>
                            <td class="text-end">${{ number_format((float) ($inv->total_paid ?? 0), 2) }}</td>
                            <td class="text-end">${{ number_format((float) $inv->balance_due, 2) }}</td>
                            <td class="text-muted">{{ $inv->due_date?->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No invoices.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $invoices->links() }}</div>
        @endif

        @if($tab === 'contracts')
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Start</th>
                            <th>End</th>
                            <th class="text-end">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($contracts as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->title }}</td>
                            <td><span class="badge bg-secondary">{{ $c->status_label }}</span></td>
                            <td class="text-muted">{{ $c->start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-muted">{{ $c->end_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-end">${{ number_format((float) $c->value, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No contracts.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $contracts->links() }}</div>
        @endif

        @if($tab === 'documents')
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($documents as $d)
                        <tr>
                            <td class="fw-semibold">{{ $d->title }}</td>
                            <td><span class="badge bg-secondary">{{ $d->category_label }}</span></td>
                            <td>{{ $d->uploader?->name ?? '—' }}</td>
                            <td class="text-muted">{{ $d->created_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No documents.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $documents->links() }}</div>
        @endif

        @if($tab === 'storage')
            <div class="alert alert-info">
                Storage provider connections (AWS S3 / Dropbox / Google Drive) are not configured yet.
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold">AWS S3</div>
                            <div class="text-muted small">Coming soon</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold">Dropbox</div>
                            <div class="text-muted small">Coming soon</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold">Google Drive</div>
                            <div class="text-muted small">Coming soon</div>
                        </div>
                    </div>
                </div>
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
                        <tr><td colspan="4" class="text-muted">No activity.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $activities->links() }}</div>
        @endif

        @if($tab === 'notes')
            <div class="mb-2 text-muted">Internal notes are only visible to admins.</div>
            <textarea class="form-control" rows="6" wire:model.live.debounce.400ms="notes"></textarea>
            <div class="mt-2 d-flex justify-content-end">
                <button type="button" class="btn btn-primary" wire:click="saveNotes">Save Notes</button>
            </div>
        @endif
    </div>
</div>
</div>

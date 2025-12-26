<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Webhook Management</h2>
                <div class="text-muted">Create endpoints, test delivery, and review history.</div>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create webhook endpoint</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Client</label>
                        <select class="form-select" wire:model="clientId">
                            <option value="">Select client…</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('clientId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Event</label>
                        <select class="form-select" wire:model="eventType">
                            @foreach($supportedEvents as $ev)
                                <option value="{{ $ev }}">{{ $ev }}</option>
                            @endforeach
                        </select>
                        @error('eventType') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Webhook URL</label>
                        <input type="url" class="form-control" wire:model.defer="webhookUrl" placeholder="https://example.com/webhook">
                        @error('webhookUrl') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Secret (optional)</label>
                        <input type="text" class="form-control" wire:model.defer="secret" placeholder="Used for HMAC signature verification">
                        @error('secret') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" wire:model="isActive">
                        <span class="form-check-label">Active</span>
                    </label>

                    <button class="btn btn-primary" wire:click="createWebhook">Create</button>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Signing</h3>
                </div>
                <div class="card-body">
                    <div class="text-muted small">
                        Requests are signed with HMAC SHA-256:
                        <div class="mt-2"><code>X-Webhook-Signature: sha256=HMAC(secret, timestamp + "." + rawBody)</code></div>
                        <div class="mt-2"><code>X-Webhook-Timestamp</code>, <code>X-Webhook-Event</code>, <code>X-Webhook-Delivery</code></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Endpoints</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Event</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($endpoints as $ep)
                            <tr class="{{ $selectedEndpointId === $ep->id ? 'table-active' : '' }}">
                                <td>{{ $ep->id }}</td>
                                <td class="text-muted">#{{ $ep->client_id }}</td>
                                <td><code>{{ $ep->event_type }}</code></td>
                                <td class="text-truncate" style="max-width: 240px;">
                                    <span title="{{ $ep->webhook_url }}">{{ $ep->webhook_url }}</span>
                                </td>
                                <td>
                                    @if($ep->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list flex-nowrap">
                                        <button class="btn btn-sm" wire:click="selectEndpoint({{ $ep->id }})">History</button>
                                        <button class="btn btn-sm btn-outline-primary" wire:click="testWebhook({{ $ep->id }})">Test</button>
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="toggleWebhook({{ $ep->id }})">
                                            {{ $ep->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="deleteWebhook({{ $ep->id }})"
                                            onclick="return confirm('Delete this webhook endpoint?')">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No webhook endpoints yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $endpoints->links() }}
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Delivery history @if($selectedEndpointId) <span class="text-muted">for endpoint #{{ $selectedEndpointId }}</span> @endif</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Delivery</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>HTTP</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($deliveries as $d)
                            <tr>
                                <td class="text-muted"><code>{{ $d->delivery_id }}</code></td>
                                <td><code>{{ $d->event_type }}</code></td>
                                <td>
                                    @php
                                        $badge = match($d->status) {
                                            'succeeded' => 'bg-success',
                                            'failed' => 'bg-danger',
                                            'running' => 'bg-warning',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $d->status }}</span>
                                </td>
                                <td>{{ $d->attempts }}</td>
                                <td>{{ $d->response_status ?? '—' }}</td>
                                <td class="text-muted">{{ $d->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Select an endpoint to view recent deliveries.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


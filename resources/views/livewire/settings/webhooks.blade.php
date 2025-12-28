<div>
    <h2 class="mb-3">Webhook Integrations</h2>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plug mr-1"></i> Add Webhook Endpoint</h3>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="form-group">
                        <label class="mb-1">Client scope (optional)</label>
                        <select class="form-control" wire:model.defer="client_id">
                            <option value="">Global (all clients)</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Global endpoints receive events for all clients.</small>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Event</label>
                        <select class="form-control" wire:model.defer="event_type">
                            @foreach($this->eventOptions as $ev)
                                <option value="{{ $ev }}">{{ $ev }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Format</label>
                        <select class="form-control" wire:model.defer="format">
                            <option value="generic">Generic (JSON)</option>
                            <option value="zapier">Zapier</option>
                            <option value="make">Make.com</option>
                            <option value="slack">Slack</option>
                            <option value="teams">Microsoft Teams</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Webhook URL</label>
                        <input class="form-control" wire:model.defer="webhook_url" placeholder="https://example.com/webhook">
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Secret (optional)</label>
                        <input class="form-control" wire:model.defer="secret" placeholder="leave blank to auto-generate">
                        <small class="text-muted">Used to sign `X-Webhook-Signature` (HMAC SHA-256).</small>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Extra headers (JSON, optional)</label>
                        <textarea class="form-control" rows="3" wire:model.defer="headers_json" placeholder='{"Authorization":"Bearer ..."}'></textarea>
                    </div>

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input" id="is_active" wire:model.defer="is_active">
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>

                    <button class="btn btn-primary" wire:click="createEndpoint">
                        <i class="fas fa-plus mr-1"></i> Create
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Endpoints</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Event</th>
                                    <th>Format</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($endpoints as $ep)
                                    <tr class="{{ $selectedEndpointId === $ep->id ? 'table-info' : '' }}">
                                        <td>{{ $ep->id }}</td>
                                        <td class="text-muted">{{ $ep->client?->company_name ?? 'Global' }}</td>
                                        <td><code>{{ $ep->event_type }}</code></td>
                                        <td class="text-muted">{{ $ep->format }}</td>
                                        <td>
                                            <span class="badge badge-{{ $ep->is_active ? 'success' : 'secondary' }}">
                                                {{ $ep->is_active ? 'active' : 'disabled' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <button class="btn btn-sm btn-outline-info" wire:click="selectEndpoint({{ $ep->id }})">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary" wire:click="testEndpoint({{ $ep->id }})">
                                                <i class="fas fa-vial"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="toggleActive({{ $ep->id }})">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" wire:click="deleteEndpoint({{ $ep->id }})" onclick="return confirm('Delete this webhook?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted text-center py-4">No webhooks configured.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i> Delivery History</h3>
                    @if($selectedEndpointId)
                        <div class="card-tools text-muted">Endpoint #{{ $selectedEndpointId }}</div>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if(!$selectedEndpointId)
                        <div class="p-3 text-muted">Select an endpoint to view recent deliveries.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>Event</th>
                                        <th>Attempt</th>
                                        <th>Status</th>
                                        <th>HTTP</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logs as $l)
                                        <tr>
                                            <td class="text-muted">{{ $l->created_at?->toDayDateTimeString() }}</td>
                                            <td><code>{{ $l->event_type }}</code></td>
                                            <td>{{ $l->attempt }}</td>
                                            <td>
                                                <span class="badge badge-{{ $l->succeeded ? 'success' : 'danger' }}">
                                                    {{ $l->succeeded ? 'ok' : 'failed' }}
                                                </span>
                                            </td>
                                            <td class="text-muted">{{ $l->http_status ?? '—' }}</td>
                                            <td class="text-muted">{{ $l->duration_ms ? $l->duration_ms . 'ms' : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted text-center py-4">No deliveries yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>


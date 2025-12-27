<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Providers</h2>
            <div class="text-muted small">Database overrides for provider keys/models/pricing. API keys are encrypted at rest.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.usage') }}">Usage</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.tasks') }}">Task config</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.audit') }}">Audit log</a>
            @if($canEdit)
                <a class="btn btn-primary" href="{{ route('admin.ai.providers.create') }}">Add provider</a>
            @endif
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(!$canEdit)
        <div class="alert alert-info">Read-only: only super admins can edit provider settings or API keys.</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>API Key</th>
                            <th class="text-end">Cost/1K in</th>
                            <th class="text-end">Cost/1K out</th>
                            <th class="text-end">RPM</th>
                            <th class="text-end">Priority</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($providers as $p)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-uppercase">{{ $p->name }}</div>
                                    @if($p->is_default)
                                        <span class="badge bg-success">default</span>
                                    @endif
                                </td>
                                <td>{{ $p->model_name ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $p->status === 'active' ? 'success' : 'secondary' }}">{{ $p->status }}</span>
                                </td>
                                <td>{{ $this->maskKey($p->api_key) }}</td>
                                <td class="text-end">{{ $p->cost_per_1k_input_tokens !== null ? '$' . number_format((float)$p->cost_per_1k_input_tokens, 6) : '—' }}</td>
                                <td class="text-end">{{ $p->cost_per_1k_output_tokens !== null ? '$' . number_format((float)$p->cost_per_1k_output_tokens, 6) : '—' }}</td>
                                <td class="text-end">{{ $p->rate_limit_per_minute ?? '—' }}</td>
                                <td class="text-end">
                                    @if($canEdit)
                                        <input type="number" class="form-control form-control-sm text-end"
                                               value="{{ $p->priority_order }}"
                                               style="width: 110px; display:inline-block;"
                                               wire:change="updatePriority({{ $p->id }}, $event.target.value)">
                                    @else
                                        {{ $p->priority_order }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($canEdit)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.ai.providers.edit', ['provider' => $p->id]) }}">Edit</a>
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="toggleStatus({{ $p->id }})">
                                            {{ $p->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" wire:click="setDefault({{ $p->id }})">Set default</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $p->id }})"
                                                onclick="return confirm('Delete this provider configuration?')">Delete</button>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if($providers->isEmpty())
                            <tr><td colspan="9" class="text-muted p-3">No provider configurations yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


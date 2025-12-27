<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">{{ $providerId ? 'Edit provider' : 'Add provider' }}</h2>
            <div class="text-muted small">Only super admins can edit API keys. Leave key blank to keep existing.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.providers') }}">Back</a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label">Provider</label>
                    <select class="form-select" wire:model="name">
                        @foreach($providerOptions as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="inactive">inactive</option>
                        <option value="active">active</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Priority order</label>
                    <input class="form-control" type="number" wire:model="priority_order" min="0">
                </div>

                <div class="col-12">
                    <label class="form-label">API key</label>
                    <input class="form-control" type="password" wire:model="api_key" placeholder="•••••••• (leave blank to keep existing)">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">API endpoint (optional)</label>
                    <input class="form-control" wire:model="api_endpoint" placeholder="https://...">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Model</label>
                    @if(count($modelOptions) > 0)
                        <select class="form-select" wire:model="model_name">
                            <option value="">(default)</option>
                            @foreach($modelOptions as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    @else
                        <input class="form-control" wire:model="model_name" placeholder="Model name">
                    @endif
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Cost/1K input</label>
                    <input class="form-control" wire:model="cost_per_1k_input_tokens" placeholder="0.00015">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Cost/1K output</label>
                    <input class="form-control" wire:model="cost_per_1k_output_tokens" placeholder="0.00060">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Rate limit (RPM)</label>
                    <input class="form-control" type="number" wire:model="rate_limit_per_minute" min="1">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_default" wire:model="is_default">
                        <label class="form-check-label" for="is_default">Set as default</label>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-outline-secondary" wire:click="testConnection" wire:loading.attr="disabled">Test connection</button>
                <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">Save</button>
            </div>

            @if($testMessage)
                <div class="mt-3 alert alert-{{ $testOk ? 'success' : 'danger' }}">
                    {{ $testMessage }}
                </div>
            @endif
        </div>
    </div>
</div>


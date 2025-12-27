<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Task Configuration</h2>
            <div class="text-muted small">Override task routing, fallbacks, feature toggles, and budgets.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.providers') }}">Providers</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.usage') }}">Usage</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.audit') }}">Audit log</a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><div class="card-title mb-0">Feature toggles</div></div>
        <div class="card-body">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="globalEnabled" wire:model="globalEnabled">
                <label class="form-check-label" for="globalEnabled">Enable AI globally</label>
            </div>

            <div class="row g-2">
                @foreach($knownTasks as $key => $label)
                    <div class="col-12 col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ft_{{ $key }}" wire:model="featureToggles.{{ $key }}">
                            <label class="form-check-label" for="ft_{{ $key }}">{{ $label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-muted small mt-2">If a task is unchecked, calls using that `task_type` will be blocked in the provider manager.</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><div class="card-title mb-0">Task → provider mapping</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th style="width: 220px;">Provider</th>
                            <th>Model</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($knownTasks as $key => $label)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $label }}</div>
                                    <div class="text-muted small">{{ $key }}</div>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" wire:model="taskModels.{{ $key }}.provider">
                                        @foreach($providerOptions as $pKey => $pLabel)
                                            <option value="{{ $pKey }}">{{ $pLabel }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" wire:model="taskModels.{{ $key }}.model" placeholder="(optional)">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><div class="card-title mb-0">Fallback & budget</div></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label">Fallback order (comma-separated)</label>
                    <input class="form-control" wire:model="fallbackOrderCsv" placeholder="openai,openrouter,claude,perplexity,asksage">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Monthly budget (USD, 0 disables)</label>
                    <input class="form-control" wire:model="monthlyBudgetUsd" placeholder="50">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Alert threshold (0-1)</label>
                    <input class="form-control" wire:model="alertPct" placeholder="0.8">
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="disableBudget" wire:model="disableWhenExceeded">
                        <label class="form-check-label" for="disableBudget">Auto-disable when exceeded</label>
                    </div>
                </div>
            </div>
            <div class="text-muted small mt-2">
                Note: enforcement is applied in `AIProviderManager::withFallback()` when auto-disable is enabled.
            </div>
        </div>
    </div>

    <div>
        <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">Save configuration</button>
    </div>
</div>


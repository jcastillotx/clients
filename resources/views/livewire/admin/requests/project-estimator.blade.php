<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin · Request #{{ $request->id }}</div>
            <h2 class="page-title mb-0">Project Estimator</h2>
            <div class="text-muted small">{{ $request->title }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.requests.show', $request) }}" class="btn btn-outline-secondary">Back to request</a>
            <button class="btn btn-outline-primary" wire:click="generateAiEstimate" wire:loading.attr="disabled">Load AI Estimate</button>
            <button class="btn btn-primary" wire:click="saveEstimate" wire:loading.attr="disabled">Save</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Tasks & Hours</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th style="width: 110px;">Phase</th>
                                    <th style="width: 90px;">Optional</th>
                                    <th style="width: 90px;">Include</th>
                                    <th class="text-end" style="width: 90px;">Low</th>
                                    <th class="text-end" style="width: 90px;">Mid</th>
                                    <th class="text-end" style="width: 90px;">High</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $i => $t)
                                    <tr>
                                        <td>
                                            <input class="form-control form-control-sm mb-1" wire:model="tasks.{{ $i }}.name" placeholder="Task name">
                                            <textarea class="form-control form-control-sm" rows="2" wire:model="tasks.{{ $i }}.description" placeholder="Description (optional)"></textarea>
                                        </td>
                                        <td>
                                            <input class="form-control form-control-sm" wire:model="tasks.{{ $i }}.phase" placeholder="e.g. Discovery">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input" wire:model="tasks.{{ $i }}.optional">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input" wire:model="tasks.{{ $i }}.included" @disabled(empty($t['optional']))>
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.25" min="0" class="form-control form-control-sm text-end" wire:model="tasks.{{ $i }}.hours_low">
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.25" min="0" class="form-control form-control-sm text-end" wire:model="tasks.{{ $i }}.hours_mid">
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.25" min="0" class="form-control form-control-sm text-end" wire:model="tasks.{{ $i }}.hours_high">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">No tasks yet. Click “Load AI Estimate”.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small">
                        Optional items can be included/excluded by the client during approval.
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0">SOW</div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" wire:click="draftSow" wire:loading.attr="disabled">Draft SOW (AI)</button>
                        <button class="btn btn-outline-success btn-sm" wire:click="generateSowPdf" wire:loading.attr="disabled">Generate SOW PDF</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="fw-semibold mb-1">Executive summary</div>
                            <textarea class="form-control" rows="3" wire:model="sow_sections.executive_summary" placeholder="(AI draft will populate this)"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="fw-semibold mb-1">Scope overview</div>
                            <textarea class="form-control" rows="3" wire:model="sow_sections.scope_overview"></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="fw-semibold mb-1">Timeline overview</div>
                            <textarea class="form-control" rows="3" wire:model="sow_sections.timeline_overview"></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="fw-semibold mb-1">Investment overview</div>
                            <textarea class="form-control" rows="3" wire:model="sow_sections.investment_overview"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="fw-semibold mb-1">Terms overview</div>
                            <textarea class="form-control" rows="3" wire:model="sow_sections.terms_overview"></textarea>
                        </div>
                        @if($estimateRecord?->sow_contract_id)
                            <div class="col-12">
                                <div class="text-muted small">
                                    SOW Contract created: <a href="{{ route('contracts.show', $estimateRecord->sow_contract_id) }}">View contract</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Pricing</div>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Base rate ($/hr)</label>
                            <input type="number" step="1" min="0" class="form-control" wire:model.live="base_rate">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Complexity (1-10)</label>
                            <input type="number" step="1" min="1" max="10" class="form-control" wire:model.live="complexity_score">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Markup %</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model.live="markup_pct">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Contingency %</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model.live="contingency_pct">
                        </div>
                    </div>

                    <hr>

                    <div class="text-muted small mb-2">
                        Client tier: <strong>{{ $request->client?->tier ?? '—' }}</strong>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Scenario</th>
                                    <th class="text-end">Hours</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['low' => 'Low', 'mid' => 'Mid', 'high' => 'High'] as $k => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end">{{ number_format((float)($pricing['totals'][$k]['hours'] ?? 0), 1) }}</td>
                                        <td class="text-end"><strong>${{ number_format((float)($pricing['totals'][$k]['total'] ?? 0), 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid mt-2">
                        <button class="btn btn-outline-primary" wire:click="sendToClient" wire:loading.attr="disabled">
                            Send to Client
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Historical comparison</div>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        Median variance (actual/estimated): <strong>{{ $historicalVariance['median_ratio'] ?? '—' }}</strong>
                        @if(isset($historicalVariance['count']))
                            <span class="text-muted">(n={{ $historicalVariance['count'] }})</span>
                        @endif
                    </div>
                    <div class="text-muted small">
                        Avg estimated hours (similar): <strong>{{ $avgEstimated ? number_format($avgEstimated, 1) : '—' }}</strong><br>
                        Avg actual hours (similar): <strong>{{ $avgActual ? number_format($avgActual, 1) : '—' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">AI Analysis</div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($analysisStatus)
                <span class="badge bg-{{ $analysisStatus === 'completed' ? 'success' : ($analysisStatus === 'failed' ? 'danger' : 'warning') }}">
                    {{ ucfirst($analysisStatus) }}
                </span>
            @endif
            @if(!is_null($analysisCost))
                <span class="text-muted small">AI analysis cost: <strong>${{ number_format($analysisCost, 2) }}</strong></span>
            @endif
        </div>
    </div>

    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-12 col-md-4">
                <label class="form-label">Provider</label>
                <select class="form-select" wire:model.live="provider">
                    @foreach($providers as $p)
                        <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Model (optional)</label>
                <input type="text" class="form-control" wire:model.live="model" placeholder="Leave blank for routing defaults">
            </div>
            <div class="col-12 col-md-3 d-grid">
                <button class="btn btn-outline-primary" wire:click="runAnalysis" wire:loading.attr="disabled">
                    Re-analyze
                </button>
            </div>
        </div>

        @php
            $triage = (array) (($analysis['triage'] ?? $analysis) ?: []);
            $clarify = (array) (($analysis['clarification'] ?? []) ?: []);
            $scope = (array) (($analysis['scope'] ?? []) ?: []);
            $complexity = (int) ($triage['complexity_score'] ?? 0);
            $priority = (string) ($triage['suggested_priority'] ?? '');
            $hours = $triage['estimated_hours'] ?? null;
        @endphp

        @if(!$analysis)
            <div class="text-muted">
                No AI analysis yet. Click <strong>Re-analyze</strong> to generate one.
            </div>
        @else
            <div class="accordion" id="aiAnalysisAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="ai-heading-1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ai-collapse-1" aria-expanded="true" aria-controls="ai-collapse-1">
                            Triage Summary
                        </button>
                    </h2>
                    <div id="ai-collapse-1" class="accordion-collapse collapse show" aria-labelledby="ai-heading-1" data-bs-parent="#aiAnalysisAccordion">
                        <div class="accordion-body">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                @if($priority !== '')
                                    <span class="badge bg-info">Priority: {{ ucfirst($priority) }}</span>
                                @endif
                                @if($complexity > 0)
                                    <span class="badge bg-secondary">Complexity: {{ $complexity }}/10</span>
                                @endif
                                @if(!is_null($hours))
                                    <span class="badge bg-primary">Estimate: {{ is_numeric($hours) ? number_format((float)$hours, 1) : $hours }} hours</span>
                                @endif
                            </div>

                            @if($complexity > 0)
                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Complexity score</div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-{{ $complexity >= 8 ? 'danger' : ($complexity >= 5 ? 'warning' : 'success') }}"
                                             role="progressbar"
                                             style="width: {{ min(100, max(0, $complexity * 10)) }}%;">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(!empty($triage['summary_for_admin']))
                                <div class="mb-2">
                                    <div class="fw-semibold">Admin summary</div>
                                    <div class="text-muted" style="white-space: pre-wrap;">{{ $triage['summary_for_admin'] }}</div>
                                </div>
                            @endif

                            @if(!empty($triage['potential_issues']) && is_array($triage['potential_issues']))
                                <div class="mt-3">
                                    <div class="fw-semibold">Potential issues</div>
                                    <ul class="mb-0">
                                        @foreach($triage['potential_issues'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="ai-heading-2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ai-collapse-2" aria-expanded="false" aria-controls="ai-collapse-2">
                            Suggestions (apply / override)
                        </button>
                    </h2>
                    <div id="ai-collapse-2" class="accordion-collapse collapse" aria-labelledby="ai-heading-2" data-bs-parent="#aiAnalysisAccordion">
                        <div class="accordion-body">
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Suggested type</label>
                                    <input type="text" class="form-control" wire:model.defer="suggested_type" placeholder="e.g. web_development">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Suggested priority</label>
                                    <input type="text" class="form-control" wire:model.defer="suggested_priority" placeholder="low|medium|high|urgent">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Estimated hours</label>
                                    <input type="number" step="0.25" min="0" class="form-control" wire:model.defer="suggested_estimated_hours" placeholder="e.g. 6.5">
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                                    <button class="btn btn-success" wire:click="acceptAiSuggestions" wire:loading.attr="disabled">
                                        Accept AI Suggestions
                                    </button>
                                    <button class="btn btn-outline-primary" wire:click="saveOverrides" wire:loading.attr="disabled">
                                        Save Overrides
                                    </button>
                                </div>
                            </div>

                            @if(!empty($triage['recommended_actions']) && is_array($triage['recommended_actions']))
                                <div class="mt-3">
                                    <div class="fw-semibold">Recommended actions</div>
                                    <ul class="mb-0">
                                        @foreach($triage['recommended_actions'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="ai-heading-3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ai-collapse-3" aria-expanded="false" aria-controls="ai-collapse-3">
                            Clarifying Questions for Client
                        </button>
                    </h2>
                    <div id="ai-collapse-3" class="accordion-collapse collapse" aria-labelledby="ai-heading-3" data-bs-parent="#aiAnalysisAccordion">
                        <div class="accordion-body">
                            @if(!empty($clarify['clarifying_questions']) && is_array($clarify['clarifying_questions']))
                                <ul class="mb-0">
                                    @foreach($clarify['clarifying_questions'] as $q)
                                        <li>{{ $q }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-muted">No clarifying questions returned.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="ai-heading-4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ai-collapse-4" aria-expanded="false" aria-controls="ai-collapse-4">
                            Draft Scope
                        </button>
                    </h2>
                    <div id="ai-collapse-4" class="accordion-collapse collapse" aria-labelledby="ai-heading-4" data-bs-parent="#aiAnalysisAccordion">
                        <div class="accordion-body">
                            @if(!empty($scope['scope_markdown']))
                                <div class="border rounded p-2" style="white-space: pre-wrap;">{{ $scope['scope_markdown'] }}</div>
                            @else
                                <div class="text-muted">No scope document returned.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>


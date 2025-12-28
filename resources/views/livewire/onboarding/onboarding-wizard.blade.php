<div>
    <h2 class="mb-3">Onboarding</h2>

    <div class="row">
        <div class="col-lg-4">
            <livewire:onboarding.onboarding-progress />
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-play-circle mr-1"></i> Welcome</h3>
                </div>
                <div class="card-body">
                    <div class="text-muted">Resources</div>
                    <ul class="mb-0">
                        <li>Welcome video (placeholder)</li>
                        <li>Quick-start checklist</li>
                        <li>Support: {{ config('client-portal.support_email') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-check mr-1"></i> Brand Discovery Questionnaire</h3>
                </div>
                <div class="card-body">
                    @if(!$questionnaire)
                        <div class="text-muted">No questionnaire available.</div>
                    @else
                        @if($questionnaire->status === 'submitted')
                            <div class="alert alert-success">
                                Thanks — your questionnaire has been submitted.
                            </div>
                        @endif

                        <div class="text-muted small mb-2">Status: <strong>{{ $questionnaire->status }}</strong></div>

                        @foreach((array)($questionnaire->questions ?? []) as $q)
                            @php
                                $key = $q['key'] ?? null;
                                $type = $q['type'] ?? 'text';
                                $label = $q['label'] ?? $key;
                                $required = !empty($q['required']);
                                $opts = (array)($q['options'] ?? []);
                            @endphp
                            @if($key)
                                <div class="mb-3">
                                    <label class="font-weight-bold mb-1">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>

                                    @if($type === 'textarea')
                                        <textarea class="form-control" rows="3" wire:model.defer="answers.{{ $key }}"></textarea>
                                    @elseif($type === 'select')
                                        <select class="form-control" wire:model.defer="answers.{{ $key }}">
                                            <option value="">Select…</option>
                                            @foreach($opts as $o)
                                                <option value="{{ $o }}">{{ $o }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($type === 'multiselect')
                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                            @foreach($opts as $o)
                                                <label class="badge badge-light p-2" style="cursor:pointer;">
                                                    <input type="checkbox" wire:model.defer="answers.{{ $key }}" value="{{ $o }}"> {{ $o }}
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <input class="form-control" type="text" wire:model.defer="answers.{{ $key }}">
                                    @endif
                                </div>
                            @endif
                        @endforeach

                        <div class="d-flex flex-wrap" style="gap: 8px;">
                            <button class="btn btn-outline-secondary" wire:click="saveProgress">Save progress</button>
                            <button class="btn btn-primary" wire:click="submitQuestionnaire">Submit</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>


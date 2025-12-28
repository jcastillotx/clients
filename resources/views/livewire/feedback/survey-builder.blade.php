<div>
    <h2 class="mb-3">Survey builder</h2>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Surveys</h3>
                </div>
                <div class="card-body">
                    <select class="form-control" wire:model="surveyId" wire:change="loadSurvey">
                        <option value="">New…</option>
                        @foreach($surveys as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->type ?? '—' }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">Pick an existing survey to edit, or select “New…”.</small>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Editor</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="col-md-8">
                            <label class="mb-1">Name</label>
                            <input class="form-control" wire:model="name">
                        </div>
                        <div class="col-md-4">
                            <label class="mb-1">Type</label>
                            <select class="form-control" wire:model="type">
                                <option value="satisfaction">satisfaction</option>
                                <option value="project_completion">project_completion</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <label class="mb-1">Description</label>
                        <textarea class="form-control" rows="2" wire:model="description"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="col">
                            <label class="mb-1">Active</label>
                            <div><input type="checkbox" wire:model="isActive"></div>
                        </div>
                        <div class="col">
                            <label class="mb-1">Anonymous allowed</label>
                            <div><input type="checkbox" wire:model="anonymousAllowed"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="font-weight-bold">Questions</div>
                        <button class="btn btn-sm btn-outline-primary" wire:click="addQuestion"><i class="fas fa-plus mr-1"></i> Add</button>
                    </div>

                    @foreach($questions as $i => $q)
                        <div class="border rounded p-2 mt-2">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="mb-1">Type</label>
                                    <select class="form-control" wire:model="questions.{{ $i }}.type">
                                        <option value="text">text</option>
                                        <option value="rating">rating</option>
                                        <option value="nps">nps</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <label class="mb-1">Prompt</label>
                                    <input class="form-control" wire:model="questions.{{ $i }}.prompt">
                                </div>
                                <div class="col-md-2">
                                    <label class="mb-1">Order</label>
                                    <input type="number" class="form-control" wire:model="questions.{{ $i }}.sort_order">
                                </div>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" wire:model="questions.{{ $i }}.is_required" id="req_{{ $i }}">
                                <label class="form-check-label" for="req_{{ $i }}">Required</label>
                            </div>
                        </div>
                    @endforeach

                    <button class="btn btn-primary mt-3" wire:click="save"><i class="fas fa-save mr-1"></i> Save survey</button>
                </div>
            </div>
        </div>
    </div>
</div>


<x-app-layout>
    <x-slot name="header">Questionnaire builder</x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-1"></i> Questionnaire Builder</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="mb-1">Title</label>
                <input class="form-control" wire:model.defer="title">
            </div>
            <div class="form-group">
                <label class="mb-1">Type</label>
                <input class="form-control" wire:model.defer="questionnaireType">
                <small class="text-muted">Examples: intake, brand_discovery, content_brief, custom</small>
            </div>

            <button class="btn btn-outline-primary mb-3" wire:click="addQuestion"><i class="fas fa-plus mr-1"></i> Add question</button>

            @foreach($questions as $idx => $q)
                <div class="border rounded p-3 mb-2">
                    <div class="form-row">
                        <div class="col-md-3">
                            <label class="mb-1">Key</label>
                            <input class="form-control" wire:model.defer="questions.{{ $idx }}.key">
                        </div>
                        <div class="col-md-3">
                            <label class="mb-1">Type</label>
                            <select class="form-control" wire:model.defer="questions.{{ $idx }}.type">
                                <option value="text">text</option>
                                <option value="textarea">textarea</option>
                                <option value="select">select</option>
                                <option value="multiselect">multiselect</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="mb-1">Label</label>
                            <input class="form-control" wire:model.defer="questions.{{ $idx }}.label">
                        </div>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" wire:model.defer="questions.{{ $idx }}.required" id="req{{ $idx }}">
                        <label class="form-check-label" for="req{{ $idx }}">Required</label>
                    </div>
                    <div class="text-muted small mt-1">Options editing is not implemented yet (can be edited in the JSON field directly).</div>
                </div>
            @endforeach

            <button class="btn btn-primary" wire:click="save"><i class="fas fa-save mr-1"></i> Save questionnaire</button>
        </div>
    </div>
</x-app-layout>


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Questionnaire Builder</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" wire:model.defer="title">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <input class="form-control" wire:model.defer="questionnaireType">
                        <small class="text-muted">Examples: intake, brand_discovery, content_brief, custom</small>
                    </div>

                    <button class="btn btn-outline-primary mb-3" wire:click="addQuestion">Add question</button>

                    @foreach($questions as $idx => $q)
                        <div class="border rounded p-3 mb-2">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label>Key</label>
                                    <input class="form-control" wire:model.defer="questions.{{ $idx }}.key">
                                </div>
                                <div class="col-md-3">
                                    <label>Type</label>
                                    <select class="form-control" wire:model.defer="questions.{{ $idx }}.type">
                                        <option value="text">text</option>
                                        <option value="textarea">textarea</option>
                                        <option value="select">select</option>
                                        <option value="multiselect">multiselect</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Label</label>
                                    <input class="form-control" wire:model.defer="questions.{{ $idx }}.label">
                                </div>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" wire:model.defer="questions.{{ $idx }}.required" id="req{{ $idx }}">
                                <label class="form-check-label" for="req{{ $idx }}">Required</label>
                            </div>
                            <div class="text-muted small mt-1">Options are not editable yet in this builder (can be added in DB JSON manually).</div>
                        </div>
                    @endforeach

                    <button class="btn btn-primary" wire:click="save">Save questionnaire</button>
                </div>
            </div>
        </div>
    </div>
</div>


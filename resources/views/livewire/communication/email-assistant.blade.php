<div class="card">
    <div class="card-header">
        <div class="card-title mb-0">Email Draft Assistant</div>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <label class="form-label">Purpose</label>
                <select class="form-select" wire:model.live="purpose">
                    <option value="request_update">Request update</option>
                    <option value="invoice_reminder">Invoice reminder</option>
                    <option value="project_milestone">Project milestone</option>
                    <option value="status_update">Status update</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Tone</label>
                <select class="form-select" wire:model.live="tone">
                    <option value="formal">Formal</option>
                    <option value="friendly">Friendly</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-grid align-items-end">
                <button class="btn btn-outline-primary" wire:click="draft">Draft</button>
            </div>
            <div class="col-12">
                <label class="form-label">Context JSON</label>
                <textarea class="form-control" rows="4" wire:model.defer="contextJson" placeholder='{"client":"Acme","request":"...","invoice":"..."}'></textarea>
            </div>
        </div>

        <hr>

        <div class="mb-2">
            <label class="form-label">Subject</label>
            <input class="form-control" wire:model.defer="subject">
        </div>
        <div class="mb-2">
            <label class="form-label">Body</label>
            <textarea class="form-control" rows="8" wire:model.defer="body"></textarea>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" wire:click="improve">Improve writing</button>
        </div>

        @if(!empty($bullets))
            <div class="mt-3">
                <div class="fw-semibold">Key bullets</div>
                <ul class="mb-0">
                    @foreach($bullets as $b)
                        <li>{{ $b }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>


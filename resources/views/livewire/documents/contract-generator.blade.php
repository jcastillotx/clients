<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Contract Generator</h2>
            <div class="text-muted small">Generate a contract from a template with AI.</div>
        </div>
        @if($createdContract)
            <a href="{{ route('contracts.show', $createdContract) }}" class="btn btn-outline-primary">View Contract</a>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Inputs</div></div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Client</label>
                        <select class="form-select" wire:model.live="clientId">
                            <option value="">Select client…</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Project (optional)</label>
                        <select class="form-select" wire:model.live="projectId">
                            <option value="">—</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Request (optional)</label>
                        <select class="form-select" wire:model.live="requestId">
                            <option value="">—</option>
                            @foreach($requests as $r)
                                <option value="{{ $r->id }}">{{ $r->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Template</label>
                        <select class="form-select" wire:model.live="templateId">
                            <option value="">Select template…</option>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->category }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Contract title</label>
                        <input class="form-control" wire:model.defer="title" placeholder="Contract — Client Name">
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-outline-primary" wire:click="generate" wire:loading.attr="disabled">Generate Draft</button>
                        <button class="btn btn-success" wire:click="createContract" wire:loading.attr="disabled">Create PDF Contract</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Draft (HTML)</div></div>
                <div class="card-body">
                    <textarea class="form-control" rows="18" wire:model.defer="html" placeholder="AI will generate HTML here…"></textarea>
                    <div class="text-muted small mt-2">This HTML will be rendered to PDF and attached to the Contract record (pending signature).</div>
                </div>
            </div>
        </div>
    </div>
</div>


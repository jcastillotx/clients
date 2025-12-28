<div>
    <h2 class="mb-3">Website Auditor</h2>

    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="mb-1">Website URL</label>
                <input class="form-control" wire:model.defer="website_url" placeholder="https://example.com">
                @error('website_url') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="mb-1">Max pages</label>
                        <input type="number" min="1" max="500" class="form-control" wire:model.defer="max_pages">
                        @error('max_pages') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="custom-control custom-switch mt-3">
                        <input type="checkbox" class="custom-control-input" id="use_ai" wire:model.defer="use_ai">
                        <label class="custom-control-label" for="use_ai">Use AI recommendations</label>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" wire:click="runAudit">
                <i class="fas fa-search mr-1"></i> Run Audit
            </button>

            <a class="btn btn-outline-secondary ml-2" href="{{ route('admin.marketing.audit-results') }}">
                View Results
            </a>
        </div>
    </div>
</div>


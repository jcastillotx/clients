<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Connect AWS S3</h2>
            <div class="text-muted small">Credentials are stored encrypted.</div>
        </div>
        <a href="{{ route('admin.storage') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if($testMessage)
        <div class="alert alert-success">{{ $testMessage }}</div>
    @endif
    @if($testError)
        <div class="alert alert-danger">{{ $testError }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form wire:submit.prevent="save" class="row g-3">
                @if($isAdmin)
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Client</label>
                        <select class="form-select" wire:model.live="client_id">
                            <option value="">Select a client…</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                @else
                    <input type="hidden" wire:model="client_id">
                @endif

                <div class="col-12 col-lg-6">
                    <label class="form-label">Region</label>
                    <select class="form-select" wire:model.live="region">
                        @foreach($regions as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                    @error('region') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">AWS Access Key ID</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="access_key_id">
                    @error('access_key_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">AWS Secret Access Key</label>
                    <input type="password" class="form-control" wire:model.live.debounce.350ms="secret_access_key" autocomplete="new-password">
                    @error('secret_access_key') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Bucket name</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="bucket" placeholder="my-company-bucket">
                    @error('bucket') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">Folder path (optional)</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="folder_path" placeholder="e.g. client-uploads/">
                    @error('folder_path') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="text-muted small mt-1">Limits browsing/uploads to a prefix inside the bucket.</div>
                </div>

                <div class="col-12">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model.live="is_primary">
                        <span class="form-check-label">Set as primary storage</span>
                    </label>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" wire:click="testConnection" wire:loading.attr="disabled">
                        Test connection
                    </button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        Save connection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


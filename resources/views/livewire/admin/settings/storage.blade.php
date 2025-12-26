<form wire:submit.prevent="saveStorage" class="vstack gap-3">
    <div>
        <div class="h3 mb-1">Storage defaults</div>
        <div class="text-muted small">Default provider, upload limits, allowed file types, quotas, retention &amp; backups.</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <label class="form-label">Default storage provider</label>
            <select class="form-select" wire:model.defer="state.storage.default_provider">
                <option value="s3">S3</option>
                <option value="dropbox">Dropbox</option>
                <option value="google_drive">Google Drive</option>
            </select>
            @error('state.storage.default_provider')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Max upload file size (MB)</label>
            <input type="number" class="form-control" wire:model.defer="state.storage.max_upload_mb">
            @error('state.storage.max_upload_mb')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Retention policy (days)</label>
            <input type="number" class="form-control" wire:model.defer="state.storage.retention_days">
            <div class="text-muted small mt-1">0 disables auto-delete.</div>
            @error('state.storage.retention_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Allowed file types (comma-separated extensions)</label>
            <input class="form-control" wire:model.defer="storageAllowedTypesCsv" placeholder="pdf,png,jpg,docx,xlsx">
            <div class="text-muted small mt-1">Used as defaults for validation and client uploads.</div>
        </div>
    </div>

    <div class="card bg-transparent border">
        <div class="card-body">
            <div class="fw-semibold mb-2">Storage quota per client tier (MB)</div>
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label">Basic</label>
                    <input type="number" class="form-control" wire:model.defer="state.storage.quota_by_tier_mb.basic">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Standard</label>
                    <input type="number" class="form-control" wire:model.defer="state.storage.quota_by_tier_mb.standard">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Premium</label>
                    <input type="number" class="form-control" wire:model.defer="state.storage.quota_by_tier_mb.premium">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Enterprise</label>
                    <input type="number" class="form-control" wire:model.defer="state.storage.quota_by_tier_mb.enterprise">
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="h3 mb-1">Backups</div>
            <label class="form-check mt-2">
                <input class="form-check-input" type="checkbox" wire:model.defer="state.storage.backups.enabled">
                <span class="form-check-label">Enable backups</span>
            </label>
            <div class="mt-2">
                <label class="form-label">Backup frequency</label>
                <select class="form-select" wire:model.defer="state.storage.backups.frequency">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
                @error('state.storage.backups.frequency')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="alert alert-info">
                These settings are stored &amp; cached. Enforcement (upload validation, retention cleanup, scheduled backups) should read from the settings table.
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">Save storage settings</button>
    </div>
</form>


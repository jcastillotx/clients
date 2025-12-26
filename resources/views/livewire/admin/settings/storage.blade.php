<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Provider Defaults</h5>
        <div class="form-group">
            <label class="mb-1">Default storage provider</label>
            <select class="form-control" wire:model.defer="storage.default_provider">
                <option value="s3">S3</option>
                <option value="dropbox">Dropbox</option>
                <option value="drive">Drive</option>
            </select>
        </div>
        <div class="form-group">
            <label class="mb-1">Maximum upload file size (MB)</label>
            <input type="number" class="form-control" wire:model.defer="storage.max_upload_mb" min="1">
        </div>
        <div class="form-group">
            <label class="mb-1">Allowed file types (comma separated)</label>
            <input class="form-control" wire:model.defer="storage.allowed_file_types">
        </div>
        <div class="form-group">
            <label class="mb-1">Retention policy (days)</label>
            <input type="number" class="form-control" wire:model.defer="storage.retention_days" min="0">
            <small class="text-muted">0 = keep forever. (Enforcement can be implemented via a scheduled cleanup job.)</small>
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Quota per client tier (GB)</h5>
        <div class="form-group">
            <label class="mb-1">Basic</label>
            <input type="number" class="form-control" wire:model.defer="storage.quota_basic_gb" min="0">
        </div>
        <div class="form-group">
            <label class="mb-1">Standard</label>
            <input type="number" class="form-control" wire:model.defer="storage.quota_standard_gb" min="0">
        </div>
        <div class="form-group">
            <label class="mb-1">Premium</label>
            <input type="number" class="form-control" wire:model.defer="storage.quota_premium_gb" min="0">
        </div>
        <div class="form-group">
            <label class="mb-1">Enterprise</label>
            <input type="number" class="form-control" wire:model.defer="storage.quota_enterprise_gb" min="0">
        </div>

        <h5 class="mt-4 mb-3">Backup</h5>
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="backup_enabled_sys" wire:model.defer="storage.backup_enabled">
            <label class="custom-control-label" for="backup_enabled_sys">Enable backup</label>
        </div>
        <div class="form-group">
            <label class="mb-1">Backup provider</label>
            <select class="form-control" wire:model.defer="storage.backup_provider">
                <option value="s3">S3</option>
                <option value="dropbox">Dropbox</option>
                <option value="drive">Drive</option>
            </select>
        </div>
    </div>
</div>

<button class="btn btn-primary" wire:click="saveStorage">
    <i class="fas fa-save mr-1"></i> Save Storage Settings
</button>


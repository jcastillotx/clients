<x-app-layout>
    <x-slot name="header">Storage Settings</x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cog mr-1"></i> Configuration</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="auto_sync" wire:model.defer="auto_sync_enabled">
                        <label class="custom-control-label" for="auto_sync">Auto-sync enabled</label>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Auto-sync frequency</label>
                        <select class="form-control" wire:model.defer="auto_sync_frequency">
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Quota alert threshold (%)</label>
                        <input type="number" class="form-control" wire:model.defer="quota_alert_percent" min="1" max="100">
                        <small class="text-muted">Clients are notified when a provider reaches this threshold.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="mb-1">Primary storage provider</label>
                        <select class="form-control" wire:model.defer="primary_connection_id">
                            <option value="">(none)</option>
                            @foreach($connections as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ strtoupper($c->provider) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Conflict resolution</label>
                        <select class="form-control" wire:model.defer="conflict_rule">
                            <option value="prefer_primary">Prefer primary</option>
                            <option value="prefer_newest">Prefer newest</option>
                            <option value="keep_both">Keep both (log conflicts)</option>
                        </select>
                        <small class="text-muted">Conflicts are detected when the same filename differs across providers.</small>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Folders to sync (comma separated)</label>
                        <input class="form-control" wire:model.defer="folders_csv" placeholder="e.g. ., invoices, contracts">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="backup_enabled" wire:model.defer="backup_enabled">
                        <label class="custom-control-label" for="backup_enabled">Backup enabled</label>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Backup destination</label>
                        <select class="form-control" wire:model.defer="backup_connection_id" @disabled(!$backup_enabled)>
                            <option value="">Select provider...</option>
                            @foreach($connections as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Backup execution is a placeholder; configure your provider disks first.</small>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" wire:click="save">
                <i class="fas fa-save mr-1"></i> Save Settings
            </button>
        </div>
    </div>
</x-app-layout>


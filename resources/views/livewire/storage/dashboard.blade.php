<div>
    <h2 class="mb-3">Storage Dashboard</h2>

    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-plug"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Connected Providers</span>
                    <span class="info-box-number">{{ $stats['providers'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active</span>
                    <span class="info-box-number">{{ $stats['active'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-database"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Used</span>
                    <span class="info-box-number">{{ number_format($stats['used_bytes'] / (1024 * 1024), 2) }} MB</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-secondary">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Last Sync</span>
                    <span class="info-box-number">
                        {{ $stats['last_sync_at'] ? \Carbon\Carbon::parse($stats['last_sync_at'])->diffForHumans() : '—' }}
                    </span>
                    <small class="text-muted">Conflicts: {{ $stats['unresolved_conflicts'] }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cloud mr-1"></i> Connected Storage Providers</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse($connections as $c)
                    <div class="col-lg-4">
                        <div class="card card-outline card-{{ $c->status_color }}">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="{{ $c->provider_icon_class }} mr-1"></i>
                                    {{ $c->name }}
                                    @if($c->is_primary)
                                        <span class="badge badge-primary ml-2">Primary</span>
                                    @endif
                                </h3>
                                <div class="card-tools">
                                    <span class="badge badge-{{ $c->status_color }}">{{ $c->status_label }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="text-muted mb-2">Provider: <strong>{{ strtoupper($c->provider) }}</strong></div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Usage</span>
                                        <span>
                                            {{ number_format($c->used_bytes / (1024 * 1024), 2) }} MB
                                            @if($c->quota_bytes)
                                                / {{ number_format($c->quota_bytes / (1024 * 1024), 2) }} MB
                                            @endif
                                        </span>
                                    </div>
                                    @php $pct = $c->quota_percent; @endphp
                                    <div class="progress">
                                        <div class="progress-bar bg-{{ $pct !== null && $pct >= 80 ? 'danger' : 'info' }}"
                                             role="progressbar"
                                             style="width: {{ $pct ?? 0 }}%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        {{ $pct !== null ? $pct . '%' : 'Quota not set' }}
                                    </small>
                                </div>

                                <div class="text-muted">
                                    Last sync:
                                    <strong>{{ $c->last_sync_at ? $c->last_sync_at->diffForHumans() : '—' }}</strong>
                                </div>
                                @if($c->last_error)
                                    <div class="text-danger mt-2">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ $c->last_error }}
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-outline-primary" wire:click="syncNow({{ $c->id }})">
                                    <i class="fas fa-sync mr-1"></i> Sync Now
                                </button>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('storage.settings') }}">
                                    <i class="fas fa-cog mr-1"></i> Settings
                                </a>
                                <button class="btn btn-sm btn-outline-danger float-right" wire:click="disconnect({{ $c->id }})">
                                    <i class="fas fa-unlink mr-1"></i> Disconnect
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted">No storage providers connected yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus mr-1"></i> Connect New Storage</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="mb-1">Provider</label>
                    <select class="form-control" wire:model="new_provider">
                        <option value="s3">S3</option>
                        <option value="dropbox">Dropbox</option>
                        <option value="drive">Drive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="mb-1">Name</label>
                    <input class="form-control" wire:model="new_name" placeholder="e.g. Primary S3 Bucket">
                </div>
                <div class="col-md-3">
                    <label class="mb-1">Filesystem disk</label>
                    <input class="form-control" wire:model="new_disk" placeholder="e.g. s3, dropbox, drive">
                    <small class="text-muted">Must exist in `config/filesystems.php`.</small>
                </div>
                <div class="col-md-2">
                    <label class="mb-1">Quota (GB)</label>
                    <input type="number" class="form-control" wire:model="new_quota_gb" placeholder="Optional">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" wire:click="connectNew">Connect</button>
                </div>
            </div>

            <hr>

            <div class="alert alert-info mb-0">
                <strong>Primary storage:</strong>
                {{ $primary ? ($primary->name . ' (' . strtoupper($primary->provider) . ')') : 'Not set' }}.
                You can change it in <a href="{{ route('storage.settings') }}">Storage Settings</a>.
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Sync History</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Status</th>
                                    <th>Files</th>
                                    <th>Changes</th>
                                    <th>Conflicts</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSyncLogs as $l)
                                    <tr>
                                        <td>{{ optional($l->finished_at)->toDateTimeString() ?? optional($l->started_at)->toDateTimeString() }}</td>
                                        <td>
                                            <span class="badge badge-{{ $l->status === 'success' ? 'success' : ($l->status === 'failed' ? 'danger' : 'warning') }}">
                                                {{ strtoupper($l->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $l->files_scanned }}</td>
                                        <td>+{{ $l->files_added }} / ~{{ $l->files_updated }} / -{{ $l->files_deleted }}</td>
                                        <td>{{ $l->conflicts }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted text-center py-3">No sync history yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Unresolved Conflicts</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Seen in</th>
                                    <th>Resolution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($conflicts as $c)
                                    <tr>
                                        <td>{{ $c->filename }}</td>
                                        <td>{{ is_array($c->candidates) ? count($c->candidates) : 0 }}</td>
                                        <td><span class="badge badge-warning">unresolved</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted text-center py-3">No conflicts.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    Conflicts are detected when the same filename differs across providers. Resolution rule is set in Storage Settings.
                </div>
            </div>
        </div>
    </div>
</div>


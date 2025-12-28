<div>
    <h2 class="mb-3">Admin Storage Overview</h2>

    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-building"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Clients</span>
                    <span class="info-box-number">{{ $stats['clients'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-plug"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Connections</span>
                    <span class="info-box-number">{{ $stats['connections'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-database"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Used</span>
                    <span class="info-box-number">{{ number_format($stats['used_bytes'] / (1024 * 1024 * 1024), 2) }} GB</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-secondary">
                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Est. S3 Monthly Cost</span>
                    <span class="info-box-number">${{ number_format($stats['s3_estimated_monthly_cost'], 2) }}</span>
                    <small class="text-muted">Rate: ${{ $s3Rate }}/GB-month</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <input class="form-control" placeholder="Search client/provider name..." wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3">
                    <select class="form-control" wire:model.live="provider">
                        <option value="">All providers</option>
                        <option value="s3">S3</option>
                        <option value="dropbox">Dropbox</option>
                        <option value="drive">Drive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" wire:model.live="status">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="error">Error</option>
                        <option value="disconnected">Disconnected</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <span class="badge badge-success">{{ $stats['active'] }}</span>
                    <span class="badge badge-danger ml-2">{{ $stats['error'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-1"></i> Client Storage Connections</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Primary</th>
                            <th>Used</th>
                            <th>Quota</th>
                            <th>Last Sync</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($connections as $c)
                            <tr>
                                <td>{{ $c->client?->company_name }}</td>
                                <td>{{ strtoupper($c->provider) }} — {{ $c->name }}</td>
                                <td><span class="badge badge-{{ $c->status_color }}">{{ $c->status_label }}</span></td>
                                <td>{!! $c->is_primary ? '<span class="badge badge-primary">yes</span>' : '<span class="badge badge-light">no</span>' !!}</td>
                                <td>{{ number_format($c->used_bytes / (1024 * 1024), 2) }} MB</td>
                                <td>{{ $c->quota_bytes ? number_format($c->quota_bytes / (1024 * 1024), 2) . ' MB' : '—' }}</td>
                                <td>{{ $c->last_sync_at ? $c->last_sync_at->diffForHumans() : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted text-center py-4">No connections found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($connections->hasPages())
            <div class="card-footer">
                {{ $connections->links() }}
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Recent Sync Failures</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Client</th>
                            <th>Connection</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lastFailures as $f)
                            <tr>
                                <td>{{ optional($f->finished_at)->toDateTimeString() }}</td>
                                <td>{{ $f->connection?->client?->company_name }}</td>
                                <td>{{ $f->connection?->name }}</td>
                                <td class="text-danger">{{ $f->error_message }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No failures recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


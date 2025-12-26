<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Storage Overview</h2>
            <div class="text-muted small">All clients’ storage connections, health, quotas, and estimated S3 costs.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage') }}">Storage dashboard</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Total used</div>
                    <div class="h3 mb-0">{{ number_format($totalUsed / (1024*1024*1024), 2) }} GB</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Known quota total</div>
                    <div class="h3 mb-0">{{ number_format($totalKnownLimit / (1024*1024*1024), 2) }} GB</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Estimated S3 storage cost (monthly)</div>
                    <div class="h3 mb-0">${{ number_format($s3EstimatedMonthly, 2) }}</div>
                    <div class="text-muted small">Approx. $0.023/GB-month.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <div class="card-title mb-0">Client quota usage (by tier)</div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Client</th>
                    <th>Tier</th>
                    <th class="text-end">Used</th>
                    <th class="text-end">Quota</th>
                    <th style="width: 240px;">Utilization</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($clientTotals ?? []) as $r)
                    @php
                        $pct = $r['pct'];
                        $bar = $pct === null ? 0 : min(100, (int)$pct);
                        $color = $pct !== null && $pct >= 100 ? 'danger' : ($pct !== null && $pct >= 80 ? 'warning' : 'primary');
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $r['client'] }}</td>
                        <td class="text-muted">{{ $r['tier'] }}</td>
                        <td class="text-end text-muted">{{ number_format(((int)$r['used']) / (1024*1024), 1) }} MB</td>
                        <td class="text-end text-muted">
                            {{ (int)$r['quota'] > 0 ? number_format(((int)$r['quota']) / (1024*1024), 1) . ' MB' : '—' }}
                        </td>
                        <td>
                            @if($pct === null)
                                <span class="text-muted small">No quota configured for this tier.</span>
                            @else
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ $bar }}%"></div>
                                </div>
                                <div class="text-muted small mt-1">{{ $pct }}% of tier quota</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No client usage data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            Quotas come from system settings: Storage quota per client tier. Warning threshold is 80%.
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Search client/provider</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="search" placeholder="Acme, dropbox...">
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label">Provider</label>
                    <select class="form-select" wire:model.live="provider">
                        <option value="all">All</option>
                        <option value="aws_s3">AWS S3</option>
                        <option value="dropbox">Dropbox</option>
                        <option value="google_drive">Google Drive</option>
                    </select>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="status">
                        <option value="all">All</option>
                        <option value="connected">Connected</option>
                        <option value="error">Error</option>
                        <option value="disconnected">Disconnected</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                <tr>
                    <th>Client</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Primary</th>
                    <th class="text-end">Used</th>
                    <th class="text-end">Limit</th>
                    <th>Last sync</th>
                </tr>
                </thead>
                <tbody>
                @forelse($connections as $c)
                    @php
                        $label = match ($c->provider) {
                            'aws_s3' => 'AWS S3',
                            'dropbox' => 'Dropbox',
                            'google_drive' => 'Google Drive',
                            default => $c->provider,
                        };
                        $statusColor = match ($c->status) {
                            'connected' => 'success',
                            'error' => 'danger',
                            'disconnected' => 'secondary',
                            default => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $c->client?->company_name ?: '—' }}</td>
                        <td>{{ $label }}</td>
                        <td><span class="badge bg-{{ $statusColor }}">{{ $c->status }}</span></td>
                        <td>
                            @if($c->is_primary)
                                <span class="badge bg-primary">Primary</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end text-muted">{{ number_format(((int)$c->storage_used) / (1024*1024), 1) }} MB</td>
                        <td class="text-end text-muted">
                            {{ $c->storage_limit ? number_format(((int)$c->storage_limit) / (1024*1024), 1) . ' MB' : '—' }}
                        </td>
                        <td class="text-muted">{{ $c->last_synced_at ? $c->last_synced_at->format('Y-m-d H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No connections found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $connections->links() }}
        </div>
    </div>
</div>


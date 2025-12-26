<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Storage Dashboard</h2>
            <div class="text-muted small">Manage all connected storage providers in one place.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage.files') }}">Unified files</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage.settings') }}">Settings</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage.overview') }}">Admin overview</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="text-muted small">Overall usage</div>
                            <div class="h3 mb-0">
                                {{ number_format(($totals['used'] ?? 0) / (1024*1024), 1) }} MB
                                @if(!is_null($totals['total']))
                                    <span class="text-muted">/ {{ number_format(($totals['total'] ?? 0) / (1024*1024), 1) }} MB</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div>
                                <div class="text-muted small">Connections</div>
                                <div class="fw-semibold">{{ $totals['connections'] ?? 0 }}</div>
                            </div>
                            <div>
                                <div class="text-muted small">Active</div>
                                <div class="fw-semibold">{{ $totals['connected'] ?? 0 }}</div>
                            </div>
                            <div>
                                <div class="text-muted small">Errors</div>
                                <div class="fw-semibold text-danger">{{ $totals['errors'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>

                    @php
                        $pct = null;
                        if (!is_null($totals['total']) && (int)$totals['total'] > 0) {
                            $pct = min(100, (int) round(((int)$totals['used'] / (int)$totals['total']) * 100));
                        }
                    @endphp
                    @if(!is_null($pct))
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-muted small mt-1">{{ $pct }}% used</div>
                    @else
                        <div class="text-muted small mt-3">Some providers do not report a total quota; overall % may be unavailable.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Connect new storage</div>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('admin.storage.s3.connect') }}">Connect AWS S3</a>
                    <a class="btn btn-outline-primary" href="{{ route('admin.storage.dropbox.connect') }}">Connect Dropbox</a>
                    <a class="btn btn-outline-primary" href="{{ route('admin.storage.google-drive.connect') }}">Connect Google Drive</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0">Connections</div>
        </div>
        <div class="card-body">
            @if($isAdmin)
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label">Client</label>
                        <select class="form-select" wire:model.live="client_id">
                            <option value="">All clients</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="row g-3">
                @forelse($cards as $c)
                    @php
                        $providerLabel = match ($c['provider']) {
                            'aws_s3' => 'AWS S3',
                            'dropbox' => 'Dropbox',
                            'google_drive' => 'Google Drive',
                            default => $c['provider'],
                        };
                        $providerIcon = match ($c['provider']) {
                            'aws_s3' => 'S3',
                            'dropbox' => 'Db',
                            'google_drive' => 'Dr',
                            default => strtoupper(substr((string)$c['provider'], 0, 2)),
                        };
                        $statusColor = match ($c['status']) {
                            'connected' => 'success',
                            'error' => 'danger',
                            'disconnected' => 'secondary',
                            default => 'secondary',
                        };
                        $statusLabel = match ($c['status']) {
                            'connected' => 'active',
                            default => $c['status'],
                        };
                        $pct = null;
                        if (!is_null($c['total']) && (int)$c['total'] > 0) {
                            $pct = min(100, (int) round(((int)$c['used'] / (int)$c['total']) * 100));
                        }
                    @endphp

                    <div class="col-12 col-lg-6 col-xxl-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div>
                                        <div class="text-muted small">
                                            @if(!empty($c['client_name']))
                                                {{ $c['client_name'] }} ·
                                            @endif
                                            <span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span>
                                            @if($c['is_primary'])
                                                <span class="badge bg-primary">Primary</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm bg-blue-lt">{{ $providerIcon }}</span>
                                            <div class="h3 mb-0">{{ $providerLabel }}</div>
                                        </div>
                                        <div class="text-muted small">
                                            Last sync: {{ $c['last_synced_at'] ? \Carbon\Carbon::parse($c['last_synced_at'])->format('Y-m-d H:i') : '—' }}
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button class="dropdown-item" wire:click="syncNow({{ (int)$c['id'] }})">Sync now</button>
                                            <a class="dropdown-item" href="{{ route('admin.storage.settings') }}">Settings</a>
                                            <button class="dropdown-item" wire:click="setPrimary({{ (int)$c['id'] }})">Set primary</button>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item text-danger" wire:click="disconnect({{ (int)$c['id'] }})">Disconnect</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="text-muted small">Usage</div>
                                        <div class="text-muted small">
                                            {{ number_format(((int)$c['used']) / (1024*1024), 1) }} MB
                                            @if(!is_null($c['total']))
                                                / {{ number_format(((int)$c['total']) / (1024*1024), 1) }} MB
                                            @endif
                                        </div>
                                    </div>
                                    @if(!is_null($pct))
                                        <div class="progress mt-2" style="height: 10px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="text-muted small mt-1">{{ $pct }}% used</div>
                                    @else
                                        <div class="text-muted small mt-2">Total quota not available.</div>
                                    @endif
                                </div>

                                @if($c['last_sync_status'])
                                    <div class="mt-3">
                                        <div class="text-muted small">Last sync run</div>
                                        <div class="small">
                                            <span class="badge bg-{{ $c['last_sync_status'] === 'success' ? 'success' : ($c['last_sync_status'] === 'error' ? 'danger' : 'secondary') }}">
                                                {{ $c['last_sync_status'] }}
                                            </span>
                                            @if($c['last_sync_started_at'])
                                                <span class="text-muted">· {{ \Carbon\Carbon::parse($c['last_sync_started_at'])->format('Y-m-d H:i') }}</span>
                                            @endif
                                        </div>
                                        @if($c['last_sync_message'])
                                            <div class="text-muted small mt-1">{{ $c['last_sync_message'] }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No storage connections yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="card-title mb-0">Sync history</div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Client</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th class="text-end">Files</th>
                    <th>Started</th>
                    <th>Finished</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentLogs as $l)
                    @php
                        $p = $l['provider'];
                        $pLabel = match ($p) {
                            'aws_s3' => 'AWS S3',
                            'dropbox' => 'Dropbox',
                            'google_drive' => 'Google Drive',
                            default => $p,
                        };
                        $st = $l['status'] ?? '—';
                        $stColor = match ($st) {
                            'success' => 'success',
                            'error' => 'danger',
                            'running' => 'info',
                            default => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $l['client'] ?? '—' }}</td>
                        <td class="text-muted">{{ $pLabel }}</td>
                        <td><span class="badge bg-{{ $stColor }}">{{ $st }}</span></td>
                        <td class="text-end text-muted">{{ $l['files_processed'] ?? 0 }}</td>
                        <td class="text-muted">{{ $l['started_at'] ? \Carbon\Carbon::parse($l['started_at'])->format('Y-m-d H:i') : '—' }}</td>
                        <td class="text-muted">{{ $l['finished_at'] ? \Carbon\Carbon::parse($l['finished_at'])->format('Y-m-d H:i') : '—' }}</td>
                        <td class="text-muted small">{{ $l['message'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No sync runs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>


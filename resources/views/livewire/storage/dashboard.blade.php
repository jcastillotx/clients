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
                        $statusColor = match ($c['status']) {
                            'connected' => 'success',
                            'error' => 'danger',
                            'disconnected' => 'secondary',
                            default => 'secondary',
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
                                            <span class="badge bg-{{ $statusColor }}">{{ $c['status'] }}</span>
                                            @if($c['is_primary'])
                                                <span class="badge bg-primary">Primary</span>
                                            @endif
                                        </div>
                                        <div class="h3 mb-0">{{ $providerLabel }}</div>
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
</div>


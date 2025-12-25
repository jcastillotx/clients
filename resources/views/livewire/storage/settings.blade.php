<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Storage Settings</h2>
            <div class="text-muted small">Primary provider, auto-sync rules, and per-provider folder settings.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage') }}">Dashboard</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage.files') }}">Unified files</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            @if($isAdmin)
                <div class="row g-3">
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
            <div class="text-muted small mt-2">
                Default auto-sync frequency: <strong>{{ $defaultFreq }} minutes</strong>. Quota warnings are sent at <strong>80%</strong>.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Primary</th>
                    <th>Auto sync</th>
                    <th>Frequency (min)</th>
                    <th>Conflict rule</th>
                    <th>Quota alerts</th>
                    <th>Sync fail alerts</th>
                    <th>Folder</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($connections as $i => $c)
                    @php
                        $label = match ($c['provider']) {
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
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $label }}</td>
                        <td><span class="badge bg-{{ $statusColor }}">{{ $c['status'] }}</span></td>
                        <td>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="connections.{{ $i }}.is_primary">
                                <span class="form-check-label">Primary</span>
                            </label>
                        </td>
                        <td>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="connections.{{ $i }}.auto_sync_enabled">
                                <span class="form-check-label">Enabled</span>
                            </label>
                        </td>
                        <td style="max-width: 160px;">
                            <input type="number" class="form-control" wire:model="connections.{{ $i }}.sync_frequency_minutes" min="0" max="10080" placeholder="{{ $defaultFreq }}">
                            <div class="text-muted small mt-1">0 = default</div>
                        </td>
                        <td style="max-width: 220px;">
                            <select class="form-select" wire:model="connections.{{ $i }}.conflict_strategy">
                                <option value="prefer_primary">Prefer primary</option>
                                <option value="prefer_newest">Prefer newest</option>
                                <option value="keep_both">Keep both</option>
                            </select>
                        </td>
                        <td>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="connections.{{ $i }}.quota_alerts_enabled">
                                <span class="form-check-label">80% warn</span>
                            </label>
                        </td>
                        <td>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="connections.{{ $i }}.sync_failure_alerts_enabled">
                                <span class="form-check-label">On fail</span>
                            </label>
                        </td>
                        <td style="max-width: 260px;">
                            @if($c['provider'] === 'google_drive')
                                <input type="text" class="form-control" wire:model="connections.{{ $i }}.drive_folder_id" placeholder="Drive folder ID">
                            @else
                                <input type="text" class="form-control" wire:model="connections.{{ $i }}.folder_path" placeholder="Folder path / prefix">
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-primary" wire:click="save({{ (int)$c['id'] }})" wire:loading.attr="disabled">Save</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No storage connections found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>


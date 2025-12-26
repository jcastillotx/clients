<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Permission matrix</h2>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label">Create custom role</label>
                    <input type="text" class="form-control" placeholder="e.g. project_manager" wire:model.live.debounce.350ms="newRoleName">
                </div>
                <div class="col-12 col-md-3">
                    <button type="button" class="btn btn-primary" wire:click="createRole">Create role</button>
                </div>
                <div class="col-12 col-md-3 text-muted small">
                    Roles and permissions are managed via Spatie.
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-vcenter card-table">
                <thead>
                <tr>
                    <th style="min-width: 260px;">Permission</th>
                    @foreach($roles as $r)
                        <th class="text-center" style="min-width: 140px;">{{ str_replace('_', ' ', ucfirst($r)) }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach($this->permissionGroups as $group => $perms)
                    @if(empty($perms)) @continue @endif
                    <tr>
                        <td colspan="{{ 1 + count($roles) }}" class="bg-light fw-semibold text-uppercase text-muted small">
                            {{ $group }}
                        </td>
                    </tr>
                    @foreach($perms as $p)
                        <tr>
                            <td class="text-muted">{{ $p }}</td>
                            @foreach($roles as $r)
                                @php $has = in_array($p, $this->rolePermissions[$r] ?? [], true); @endphp
                                <td class="text-center">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           @checked($has)
                                           wire:click="toggle('{{ $r }}', '{{ $p }}')">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            Tip: keep <strong>staff</strong> permissions minimal and use client assignments to scope access.
        </div>
    </div>
</div>


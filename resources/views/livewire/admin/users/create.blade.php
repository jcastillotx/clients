<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <div class="page-pretitle">Admin</div>
                <h2 class="page-title mb-0">Add User</h2>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>

        <form wire:submit.prevent="save" class="card">
            <div class="card-header">
                <div class="card-title mb-0">Account Details</div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" wire:model.live.debounce.350ms="name">
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" wire:model.live.debounce.350ms="email">
                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Role *</label>
                        <select class="form-select" wire:model.live="role">
                            @foreach($roles as $r)
                                <option value="{{ $r }}">{{ str_replace('_', ' ', ucfirst($r)) }}</option>
                            @endforeach
                        </select>
                        @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Account status</label>
                        <select class="form-select" wire:model.live="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    @if($role === 'client')
                        <div class="col-12">
                            <hr class="my-2">
                            <div class="fw-semibold">Client Link</div>
                        </div>

                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="createNewClient">
                                <span class="form-check-label">Create new client record</span>
                            </label>
                        </div>

                        @if(!$createNewClient)
                            <div class="col-12 col-md-6">
                                <label class="form-label">Existing client *</label>
                                <select class="form-select" wire:model.live="client_id">
                                    <option value="">Select a client…</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                                @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div class="col-12 col-md-6">
                                <label class="form-label">Company name *</label>
                                <input type="text" class="form-control" wire:model.live.debounce.350ms="client_company_name">
                                @error('client_company_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Contact name *</label>
                                <input type="text" class="form-control" wire:model.live.debounce.350ms="client_contact_name">
                                @error('client_contact_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Client phone</label>
                                <input type="text" class="form-control" wire:model.live.debounce.350ms="client_phone">
                                @error('client_phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    @endif

                    @if($role === 'staff')
                        <div class="col-12">
                            <hr class="my-2">
                            <div class="fw-semibold">Staff Access</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Assignment role</label>
                            <select class="form-select" wire:model.live="staffAssignmentRole">
                                <option value="account_manager">Account manager</option>
                                <option value="project_lead">Project lead</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Assigned clients</label>
                            <select class="form-select" multiple size="6" wire:model.live="assignedClientIds">
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">Staff will only see assigned clients.</div>
                            @error('assignedClientIds.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Permissions (module access)</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow:auto;">
                                @foreach($permissionGroups as $group => $perms)
                                    @if(empty($perms)) @continue @endif
                                    <div class="mb-3">
                                        <div class="fw-semibold mb-2">{{ $group }}</div>
                                        @foreach($perms as $p)
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $p }}" wire:model.live="staffPermissions">
                                                <span class="form-check-label">{{ $p }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            @error('staffPermissions.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            An invitation email with a <strong>password setup link</strong> will be sent to the user.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Create User</span>
                    <span wire:loading wire:target="save">Creating…</span>
                </button>
            </div>
        </form>
    </div>
</div>


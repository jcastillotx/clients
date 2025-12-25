<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Add User</h2>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form wire:submit.prevent="save" class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" wire:model.live.debounce.350ms="name">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" wire:model.live.debounce.350ms="email">
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Role</label>
                    <select class="form-select" wire:model.live="role">
                        @foreach($roles as $r)
                            <option value="{{ $r }}">{{ str_replace('_', ' ', ucfirst($r)) }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-4">
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
                        <div class="hr-text">Client link</div>
                    </div>

                    <div class="col-12">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.live="createNewClient">
                            <span class="form-check-label">Create new client record</span>
                        </label>
                    </div>

                    @if(!$createNewClient)
                        <div class="col-12 col-md-6">
                            <label class="form-label">Existing client</label>
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
                            <label class="form-label">Company name</label>
                            <input type="text" class="form-control" wire:model.live.debounce.350ms="client_company_name">
                            @error('client_company_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Contact name</label>
                            <input type="text" class="form-control" wire:model.live.debounce.350ms="client_contact_name">
                            @error('client_contact_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Client phone (optional)</label>
                            <input type="text" class="form-control" wire:model.live.debounce.350ms="client_phone">
                            @error('client_phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif
                @endif

                @if($role === 'staff')
                    <div class="col-12">
                        <div class="hr-text">Staff access</div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <label class="form-label">Assign to clients (staff will only see assigned clients)</label>
                        <select class="form-select" multiple size="8" wire:model.live="assignedClientIds">
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('assignedClientIds.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-xl-6">
                        <label class="form-label">Permissions (module access)</label>
                        <div class="border rounded p-2" style="max-height: 340px; overflow:auto;">
                            @foreach($permissionGroups as $group => $perms)
                                @if(empty($perms)) @continue @endif
                                <div class="mb-2">
                                    <div class="fw-semibold">{{ $group }}</div>
                                    <div class="row g-1 mt-1">
                                        @foreach($perms as $p)
                                            <div class="col-12 col-md-6">
                                                <label class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="{{ $p }}" wire:model.live="staffPermissions">
                                                    <span class="form-check-label">{{ $p }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
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

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Create user</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>


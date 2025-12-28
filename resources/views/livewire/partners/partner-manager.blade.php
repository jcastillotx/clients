<div>
    <h2 class="mb-3">Partners</h2>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-friends mr-1"></i> New partner</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Name</label>
                        <input class="form-control" wire:model="name">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Email</label>
                        <input class="form-control" wire:model="email">
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <label class="mb-1">Code</label>
                            <input class="form-control" wire:model="code">
                        </div>
                        <div class="col">
                            <label class="mb-1">Commission %</label>
                            <input class="form-control" wire:model="commissionRate">
                        </div>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="pa" wire:model="isActive">
                        <label class="form-check-label" for="pa">Active</label>
                    </div>
                    <button class="btn btn-primary mt-2" wire:click="create"><i class="fas fa-save mr-1"></i> Create</button>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Partners</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Rate</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partners as $p)
                                <tr>
                                    <td>{{ $p->name }}</td>
                                    <td><code>{{ $p->code }}</code></td>
                                    <td>{{ $p->commission_rate }}%</td>
                                    <td>{{ $p->is_active ? 'active' : 'inactive' }}</td>
                                    <td><button class="btn btn-sm btn-outline-secondary" wire:click="toggle({{ $p->id }})">Toggle</button></td>
                                </tr>
                            @endforeach
                            @if($partners->isEmpty())
                                <tr><td colspan="5" class="text-muted p-3">No partners yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


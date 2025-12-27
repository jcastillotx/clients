<x-app-layout>
    <x-slot name="header">Referrals</x-slot>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-plus mr-1"></i> Log referral</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Partner (optional)</label>
                        <select class="form-control" wire:model.defer="partnerId">
                            <option value="">None</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Referred name</label>
                        <input class="form-control" wire:model.defer="referredName">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Referred email</label>
                        <input class="form-control" wire:model.defer="referredEmail">
                    </div>
                    <button class="btn btn-primary" wire:click="create"><i class="fas fa-save mr-1"></i> Save</button>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Recent referrals</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Partner</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referrals as $r)
                                <tr>
                                    <td>{{ $r->partner?->name ?? '—' }}</td>
                                    <td>{{ $r->referred_name ?? '—' }}</td>
                                    <td>{{ $r->referred_email ?? '—' }}</td>
                                    <td>{{ $r->status }}</td>
                                    <td class="text-muted">{{ $r->created_at?->toDateString() }}</td>
                                </tr>
                            @endforeach
                            @if($referrals->isEmpty())
                                <tr><td colspan="5" class="text-muted p-3">No referrals yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


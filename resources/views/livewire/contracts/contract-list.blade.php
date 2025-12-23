<div>
    <!-- Filters -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-md-0">
                        <input type="text" 
                               wire:model.live.debounce.300ms="search" 
                               class="form-control" 
                               placeholder="Search contracts...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    @if($search || $status)
                    <button wire:click="clearFilters" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Contract List -->
    <div class="row">
        @forelse($contracts as $contract)
        <div class="col-md-6 col-lg-4">
            <div class="card {{ $contract->isPendingSignature() ? 'card-warning' : '' }}">
                <div class="card-header">
                    <h3 class="card-title">{{ Str::limit($contract->title, 30) }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $contract->status_color }}">
                            {{ $contract->status_label }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Contract #</dt>
                        <dd class="col-sm-7">{{ $contract->contract_number }}</dd>

                        <dt class="col-sm-5">Value</dt>
                        <dd class="col-sm-7">${{ number_format($contract->value, 2) }}</dd>

                        @if($contract->end_date)
                        <dt class="col-sm-5">Expires</dt>
                        <dd class="col-sm-7">{{ $contract->end_date->format('M d, Y') }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye mr-1"></i> View
                    </a>
                    @if($contract->isPendingSignature())
                    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-warning float-right">
                        <i class="fas fa-signature mr-1"></i> Sign Now
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-contract fa-4x text-muted mb-3"></i>
                    <h4>No Contracts Found</h4>
                    <p class="text-muted">
                        @if($search || $status)
                        No contracts match your search criteria.
                        <button wire:click="clearFilters" class="btn btn-link p-0">Clear filters</button>
                        @else
                        You don't have any contracts yet.
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    @if($contracts->hasPages())
    <div class="d-flex justify-content-center">
        {{ $contracts->links() }}
    </div>
    @endif
</div>

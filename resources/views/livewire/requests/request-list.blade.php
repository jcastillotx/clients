<div>
    <!-- Filters -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-md-0">
                        <input type="text" 
                               wire:model.live.debounce.300ms="search" 
                               class="form-control" 
                               placeholder="Search requests...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="type" class="form-control">
                        <option value="">All Types</option>
                        @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="priority" class="form-control">
                        <option value="">All Priorities</option>
                        @foreach($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    @if($search || $status || $type || $priority)
                    <button wire:click="clearFilters" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Request List -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th wire:click="sortBy('title')" style="cursor: pointer;">
                            Title
                            @if($sortField === 'title')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th wire:click="sortBy('created_at')" style="cursor: pointer;">
                            Created
                            @if($sortField === 'created_at')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </th>
                        <th>Due Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    <tr>
                        <td>
                            <a href="{{ route('requests.show', $request) }}" class="font-weight-bold">
                                {{ Str::limit($request->title, 40) }}
                            </a>
                        </td>
                        <td>{{ $request->type_label }}</td>
                        <td>
                            <span class="badge badge-{{ $request->status_color }}">
                                {{ $request->status_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $request->priority_color }}">
                                {{ $request->priority_label }}
                            </span>
                        </td>
                        <td>{{ $request->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($request->due_date)
                            <span class="{{ $request->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                                {{ $request->due_date->format('M d, Y') }}
                            </span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('requests.show', $request) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                <p>No requests found.</p>
                                @if($search || $status || $type || $priority)
                                <button wire:click="clearFilters" class="btn btn-outline-primary btn-sm">
                                    Clear Filters
                                </button>
                                @else
                                <a href="{{ route('requests.create') }}" class="btn btn-primary btn-sm">
                                    Create Your First Request
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>
        @if($requests->hasPages())
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>

<div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <div>
            <div class="text-sm theme-text-muted">Admin</div>
            <h2 class="text-2xl font-semibold theme-text-primary mb-0">All Requests</h2>
        </div>
        <div class="flex gap-2">
            <x-button 
                variant="primary" 
                size="md"
                href="{{ route('admin.requests.create') }}"
                icon="plus"
            >
                Create Request
            </x-button>
            <x-button 
                variant="secondary" 
                size="md"
                wire:click="$set('viewMode','kanban')"
                icon="collection"
            >
                Kanban
            </x-button>
        </div>
    </div>

    <!-- Status Summary Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 mb-3">
        @php
            $totalRequests = array_sum($statusCounts ?? []);
            $openRequests = ($statusCounts['pending'] ?? 0) + ($statusCounts['in_review'] ?? 0) + ($statusCounts['approved'] ?? 0) + ($statusCounts['in_progress'] ?? 0) + ($statusCounts['on_hold'] ?? 0);
        @endphp

        <div>
            <div class="theme-bg-card rounded-lg theme-shadow-sm theme-border-primary border">
                <div class="density-p-lg">
                    <div class="flex items-center gap-3">
                        <div>
                            <span class="bg-brand-primary text-white flex items-center justify-center w-10 h-10 rounded-full">
                                <x-icon name="clipboard-list" class="w-5 h-5" />
                            </span>
                        </div>
                        <div>
                            <div class="font-medium theme-text-primary">{{ $totalRequests }}</div>
                            <div class="theme-text-muted text-sm">Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="theme-bg-card rounded-lg theme-shadow-sm theme-border-primary border">
                <div class="density-p-lg">
                    <div class="flex items-center gap-3">
                        <div>
                            <span class="bg-blue-500 text-white flex items-center justify-center w-10 h-10 rounded-full">
                                <x-icon name="clock" class="w-5 h-5" />
                            </span>
                        </div>
                        <div>
                            <div class="font-medium theme-text-primary">{{ $openRequests }}</div>
                            <div class="theme-text-muted text-sm">Open</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="theme-bg-card rounded-lg theme-shadow-sm theme-border-primary border">
                <div class="density-p-lg">
                    <div class="flex items-center gap-3">
                        <div>
                            <span class="bg-amber-500 text-white flex items-center justify-center w-10 h-10 rounded-full">
                                <x-icon name="clock" class="w-5 h-5" />
                            </span>
                        </div>
                        <div>
                            <div class="font-medium theme-text-primary">{{ $statusCounts['pending'] ?? 0 }}</div>
                            <div class="theme-text-muted text-sm">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="theme-bg-card rounded-lg theme-shadow-sm theme-border-primary border">
                <div class="density-p-lg">
                    <div class="flex items-center gap-3">
                        <div>
                            <span class="bg-cyan-500 text-white flex items-center justify-center w-10 h-10 rounded-full">
                                <x-icon name="briefcase" class="w-5 h-5" />
                            </span>
                        </div>
                        <div>
                            <div class="font-medium theme-text-primary">{{ $statusCounts['in_progress'] ?? 0 }}</div>
                            <div class="theme-text-muted text-sm">In Progress</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="theme-bg-card rounded-lg theme-shadow-sm border {{ ($overdueCount ?? 0) > 0 ? 'border-red-500' : 'theme-border-primary' }}">
                <div class="density-p-lg">
                    <div class="flex items-center gap-3">
                        <div>
                            <span class="bg-red-500 text-white flex items-center justify-center w-10 h-10 rounded-full">
                                <x-icon name="exclamation-circle" class="w-5 h-5" />
                            </span>
                        </div>
                        <div>
                            <div class="font-medium {{ ($overdueCount ?? 0) > 0 ? 'text-red-500' : 'theme-text-primary' }}">{{ $overdueCount ?? 0 }}</div>
                            <div class="theme-text-muted text-sm">Overdue</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="theme-bg-card rounded-lg theme-shadow-sm border {{ ($unassignedCount ?? 0) > 0 ? 'border-amber-500' : 'theme-border-primary' }}">
                <div class="density-p-lg">
                    <div class="flex items-center gap-3">
                        <div>
                            <span class="bg-slate-600 text-white flex items-center justify-center w-10 h-10 rounded-full">
                                <x-icon name="user-circle" class="w-5 h-5" />
                            </span>
                        </div>
                        <div>
                            <div class="font-medium {{ ($unassignedCount ?? 0) > 0 ? 'text-amber-500' : 'theme-text-primary' }}">{{ $unassignedCount ?? 0 }}</div>
                            <div class="theme-text-muted text-sm">Unassigned</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="theme-bg-card rounded-lg theme-shadow-sm theme-border-primary border mb-3">
        <div class="density-p-lg">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium theme-text-secondary mb-1.5">Search</label>
                    <input type="text" class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Title, description, client…" wire:model.live.debounce.350ms="search">
                </div>
                <div>
                    <label class="block text-sm font-medium theme-text-secondary mb-1.5">Client (search)</label>
                    <input type="text" class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Start typing…" wire:model.live.debounce.350ms="clientSearch">
                </div>
                <div>
                    <label class="block text-sm font-medium theme-text-secondary mb-1.5">Client</label>
                    <select class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="clientId">
                        <option value="">All clients</option>
                        @foreach($clientOptions as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium theme-text-secondary mb-1.5">Assigned to</label>
                    <select class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="assignedTo">
                        <option value="">Anyone</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium theme-text-secondary mb-1.5">Status (multi)</label>
                    <select class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" multiple size="5" wire:model.live="statuses">
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium theme-text-secondary mb-1.5">Type (multi)</label>
                    <select class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" multiple size="5" wire:model.live="types">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium theme-text-secondary mb-1.5">Priority</label>
                    <select class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="priority">
                        <option value="">All</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1 lg:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium theme-text-secondary mb-1.5">From</label>
                        <input type="date" class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="dateFrom">
                    </div>
                    <div>
                        <label class="block text-sm font-medium theme-text-secondary mb-1.5">To</label>
                        <input type="date" class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="dateTo">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($selected))
        <div class="alert alert-info flex flex-wrap items-center gap-2 mb-3">
            <div class="flex-1">
                <strong>{{ count($selected) }}</strong> selected
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="flex items-center gap-1 theme-bg-card theme-border-primary border rounded-lg overflow-hidden">
                    <span class="density-px-sm density-py-sm theme-bg-secondary text-sm font-medium">Status</span>
                    <select class="density-px-sm density-py-sm border-0 focus:ring-0 theme-bg-card theme-text-primary" wire:model="bulkStatus">
                        <option value="">—</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-button variant="primary" size="sm" wire:click="applyBulkStatus">Apply</x-button>
                </div>

                @if($canAssign)
                <div class="flex items-center gap-1 theme-bg-card theme-border-primary border rounded-lg overflow-hidden">
                    <span class="density-px-sm density-py-sm theme-bg-secondary text-sm font-medium">Assign</span>
                    <select class="density-px-sm density-py-sm border-0 focus:ring-0 theme-bg-card theme-text-primary" wire:model="bulkAssignedTo">
                        <option value="">—</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                    <x-button variant="primary" size="sm" wire:click="applyBulkAssign">Apply</x-button>
                </div>
                @endif

                <div class="flex items-center gap-1 theme-bg-card theme-border-primary border rounded-lg overflow-hidden">
                    <span class="density-px-sm density-py-sm theme-bg-secondary text-sm font-medium">Priority</span>
                    <select class="density-px-sm density-py-sm border-0 focus:ring-0 theme-bg-card theme-text-primary" wire:model="bulkPriority">
                        <option value="">—</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-button variant="primary" size="sm" wire:click="applyBulkPriority">Apply</x-button>
                </div>
            </div>
        </div>
    @endif

    <div class="theme-bg-card rounded-lg theme-shadow-sm theme-border-primary border">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead class="theme-bg-card-header theme-border-primary border-b">
                <tr>
                    <th class="density-px-md density-py-sm text-left" style="width:1%">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 rounded theme-border-primary focus:ring-blue-500" wire:model="selectPage">
                    </th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">ID</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Client</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Title</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Type</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Status</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Priority</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Assigned To</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Created</th>
                    <th class="density-px-md density-py-sm text-left text-xs font-semibold theme-text-secondary uppercase tracking-wider">Due</th>
                    <th class="density-px-md density-py-sm text-right text-xs font-semibold theme-text-secondary uppercase tracking-wider">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y theme-border-muted">
                @forelse($requests as $r)
                    <tr class="hover:theme-bg-secondary transition-colors">
                        <td class="density-px-md density-py-sm">
                            <input type="checkbox" class="w-4 h-4 text-blue-600 rounded theme-border-primary focus:ring-blue-500" value="{{ $r->id }}" wire:model="selected">
                        </td>
                        <td class="density-px-md density-py-sm theme-text-muted text-sm">#{{ $r->id }}</td>
                        <td class="density-px-md density-py-sm text-sm theme-text-primary">{{ $r->client?->company_name ?? ('Client #' . $r->client_id) }}</td>
                        <td class="density-px-md density-py-sm text-sm font-semibold theme-text-primary">{{ $r->title }}</td>
                        <td class="density-px-md density-py-sm text-sm theme-text-primary">{{ $types[$r->type] ?? $r->type }}</td>
                        <td class="density-px-md density-py-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ $r->status_color }}-100 text-{{ $r->status_color }}-800">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                        </td>
                        <td class="density-px-md density-py-sm">{!! $r->priority_badge !!}</td>
                        <td class="density-px-md density-py-sm text-sm theme-text-primary">{{ $r->assignee?->name ?? '—' }}</td>
                        <td class="density-px-md density-py-sm theme-text-muted text-sm">{{ $r->created_at?->format('Y-m-d') }}</td>
                        <td class="density-px-md density-py-sm text-sm {{ $r->isOverdue() ? 'text-red-500 font-semibold' : 'theme-text-muted' }}">
                            {{ $r->due_date?->format('Y-m-d') ?? '—' }}
                        </td>
                        <td class="density-px-md density-py-sm text-right">
                            <div class="flex items-center justify-end gap-2">
                                <x-button 
                                    variant="outline-primary" 
                                    size="xs"
                                    href="{{ route('admin.requests.show', $r) }}"
                                    icon="eye"
                                >
                                    Open
                                </x-button>
                                @if($canAssign)
                                <x-button 
                                    variant="outline" 
                                    size="xs"
                                    wire:click="openAssign({{ $r->id }})"
                                    icon="user-circle"
                                >
                                    Assign
                                </x-button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="density-px-md py-8 text-center theme-text-muted">No requests found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="density-px-lg density-py-md theme-border-primary border-t theme-bg-card-footer">
            {{ $requests->links() }}
        </div>
    </div>

    @if($showAssign)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="display:block;" tabindex="-1" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative w-full max-w-2xl theme-bg-card rounded-lg theme-shadow-lg">
                    <div class="flex items-center justify-between density-px-lg density-py-md theme-border-primary border-b">
                        <h5 class="text-lg font-semibold theme-text-primary">Assign Request</h5>
                        <button type="button" class="theme-text-muted hover:theme-text-secondary" wire:click="$set('showAssign', false)" aria-label="Close">
                            <x-icon name="x" class="w-6 h-6" />
                        </button>
                    </div>
                    <div class="density-p-lg">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium theme-text-secondary mb-1.5">Assign to</label>
                                <select class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model="assignToUserId">
                                    <option value="">Unassigned</option>
                                    @foreach($staffOptions as $u)
                                        <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['email'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium theme-text-secondary mb-1.5">Due date</label>
                                <input type="date" class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model="assignDueDate">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium theme-text-secondary mb-1.5">Internal notes</label>
                                <textarea class="w-full density-px-sm density-py-sm theme-border-primary border rounded-lg theme-bg-card theme-text-primary focus:border-blue-500 focus:ring-4 focus:ring-blue-100" rows="3" placeholder="Visible to staff only…" wire:model="assignInternalNote"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input class="w-4 h-4 text-blue-600 rounded theme-border-primary focus:ring-blue-500" type="checkbox" wire:model="assignNotify">
                                    <span class="ml-2 text-sm theme-text-secondary">Email the assigned staff member</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 density-px-lg density-py-md theme-border-primary border-t theme-bg-card-footer">
                        <x-button variant="ghost" size="md" wire:click="$set('showAssign', false)">Cancel</x-button>
                        <x-button variant="primary" size="md" wire:click="saveAssignment" wire:loading.attr="disabled">Save</x-button>
                    </div>
                </div>
            </div>
        </div>
        <div class="fixed inset-0 bg-slate-900 bg-opacity-50 transition-opacity z-40"></div>
    @endif
</div>


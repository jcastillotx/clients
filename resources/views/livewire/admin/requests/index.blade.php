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

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 mb-3">
        <div class="p-6">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Search</label>
                    <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Title, description, client…" wire:model.live.debounce.350ms="search">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Client (search)</label>
                    <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Start typing…" wire:model.live.debounce.350ms="clientSearch">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Client</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="clientId">
                        <option value="">All clients</option>
                        @foreach($clientOptions as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Assigned to</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="assignedTo">
                        <option value="">Anyone</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Status (multi)</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" multiple size="5" wire:model.live="statuses">
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Type (multi)</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" multiple size="5" wire:model.live="types">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Priority</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="priority">
                        <option value="">All</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1 lg:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">From</label>
                        <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="dateFrom">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">To</label>
                        <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model.live="dateTo">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($selected))
        <div class="alert alert-info flex flex-wrap items-center gap-2">
            <div class="flex-1">
                <strong>{{ count($selected) }}</strong> selected
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="flex items-center gap-1 bg-white border border-slate-300 rounded-lg overflow-hidden">
                    <span class="px-3 py-2 bg-slate-100 text-sm font-medium">Status</span>
                    <select class="px-3 py-2 border-0 focus:ring-0" wire:model="bulkStatus">
                        <option value="">—</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 font-semibold" wire:click="applyBulkStatus">Apply</button>
                </div>

                @if($canAssign)
                <div class="flex items-center gap-1 bg-white border border-slate-300 rounded-lg overflow-hidden">
                    <span class="px-3 py-2 bg-slate-100 text-sm font-medium">Assign</span>
                    <select class="px-3 py-2 border-0 focus:ring-0" wire:model="bulkAssignedTo">
                        <option value="">—</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 font-semibold" wire:click="applyBulkAssign">Apply</button>
                </div>
                @endif

                <div class="flex items-center gap-1 bg-white border border-slate-300 rounded-lg overflow-hidden">
                    <span class="px-3 py-2 bg-slate-100 text-sm font-medium">Priority</span>
                    <select class="px-3 py-2 border-0 focus:ring-0" wire:model="bulkPriority">
                        <option value="">—</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 font-semibold" wire:click="applyBulkPriority">Apply</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left" style="width:1%">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" wire:model="selectPage">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Assigned To</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Created</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Due</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @forelse($requests as $r)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" value="{{ $r->id }}" wire:model="selected">
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-sm">#{{ $r->id }}</td>
                        <td class="px-4 py-3 text-sm">{{ $r->client?->company_name ?? ('Client #' . $r->client_id) }}</td>
                        <td class="px-4 py-3 text-sm font-semibold">{{ $r->title }}</td>
                        <td class="px-4 py-3 text-sm">{{ $types[$r->type] ?? $r->type }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ $r->status_color }}-100 text-{{ $r->status_color }}-800">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                        </td>
                        <td class="px-4 py-3">{!! $r->priority_badge !!}</td>
                        <td class="px-4 py-3 text-sm">{{ $r->assignee?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 text-sm">{{ $r->created_at?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm {{ $r->isOverdue() ? 'text-red-500 font-semibold' : 'text-slate-500' }}">
                            {{ $r->due_date?->format('Y-m-d') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.requests.show', $r) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">Open</a>
                            @if($canAssign)
                            <button type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 ml-2" wire:click="openAssign({{ $r->id }})">Assign</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-slate-500">No requests found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
            {{ $requests->links() }}
        </div>
    </div>

    @if($showAssign)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="display:block;" tabindex="-1" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                        <h5 class="text-lg font-semibold text-slate-900">Assign Request</h5>
                        <button type="button" class="text-slate-400 hover:text-slate-500" wire:click="$set('showAssign', false)" aria-label="Close">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Assign to</label>
                                <select class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model="assignToUserId">
                                    <option value="">Unassigned</option>
                                    @foreach($staffOptions as $u)
                                        <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['email'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Due date</label>
                                <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" wire:model="assignDueDate">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Internal notes</label>
                                <textarea class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100" rows="3" placeholder="Visible to staff only…" wire:model="assignInternalNote"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" type="checkbox" wire:model="assignNotify">
                                    <span class="ml-2 text-sm text-slate-700">Email the assigned staff member</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-200 bg-slate-50">
                        <button type="button" class="px-4 py-2 text-sm font-semibold text-slate-700 hover:text-slate-900" wire:click="$set('showAssign', false)">Cancel</button>
                        <button type="button" class="btn-brand-primary" wire:click="saveAssignment" wire:loading.attr="disabled">Save</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="fixed inset-0 bg-slate-900 bg-opacity-50 transition-opacity z-40"></div>
    @endif
</div>


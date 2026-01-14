<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Support</div>
            <div class="text-xl font-semibold text-slate-900">Support Ticket Management</div>
        </div>
        <a href="{{ route('admin.maintenance-plans.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Manage Maintenance Plans
        </a>
    </div>

    <!-- Status Summary Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
        @php
            $statusColors = [
                'open' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200'],
                'in_progress' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'],
                'waiting_on_client' => ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200'],
                'waiting_on_vendor' => ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200'],
                'resolved' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
                'closed' => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200'],
            ];
            $totalTickets = array_sum($statusCounts);
        @endphp

        <button wire:click="$set('status', '')"
                class="rounded-xl border {{ $status === '' ? 'border-slate-900 ring-2 ring-slate-900' : 'border-slate-200' }} bg-white p-4 text-left transition hover:shadow-md">
            <div class="text-2xl font-bold text-slate-900">{{ $totalTickets }}</div>
            <div class="text-xs font-medium text-slate-500">Total Tickets</div>
        </button>

        @foreach($statusLabels as $key => $label)
            @php
                $count = $statusCounts[$key] ?? 0;
                $colors = $statusColors[$key] ?? ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200'];
            @endphp
            <button wire:click="$set('status', '{{ $key }}')"
                    class="rounded-xl border {{ $status === $key ? 'border-slate-900 ring-2 ring-slate-900' : $colors['border'] }} {{ $colors['bg'] }} p-4 text-left transition hover:shadow-md">
                <div class="text-2xl font-bold {{ $colors['text'] }}">{{ $count }}</div>
                <div class="text-xs font-medium {{ $colors['text'] }}">{{ $label }}</div>
            </button>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="form-card">
        <div class="form-grid-5">
            <div>
                <label class="form-label-modern">Search</label>
                <input
                    wire:model.live.debounce.250ms="search"
                    type="text"
                    placeholder="Search by subject or ticket #..."
                    class="form-input"
                />
            </div>

            <div>
                <label class="form-label-modern">Status</label>
                <select wire:model.live="status" class="form-select-modern">
                    <option value="">All statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}">{{ $statusLabels[$s] ?? ucfirst(str_replace('_',' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label-modern">Category</label>
                <select wire:model.live="category" class="form-select-modern">
                    <option value="">All categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label-modern">Client</label>
                <select wire:model.live="clientId" class="form-select-modern">
                    <option value="">All clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label-modern">Billing</label>
                <select wire:model.live="billableFilter" class="form-select-modern">
                    <option value="">All</option>
                    <option value="covered">Covered by Plan</option>
                    <option value="billable">Billable</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="form-card p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Client</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned</th>
                        <th>Billing</th>
                        <th>Created</th>
                        <th class="actions text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="text-sm font-medium text-slate-900">{{ $ticket->ticket_number }}</td>
                            <td class="text-sm text-slate-600">{{ $ticket->client?->company_name ?? 'N/A' }}</td>
                            <td class="text-sm text-slate-900">
                                <div class="max-w-[20rem] truncate font-semibold">{{ $ticket->subject }}</div>
                            </td>
                            <td class="text-sm">
                                <select
                                    wire:change="updateStatus({{ $ticket->id }}, $event.target.value)"
                                    class="form-select-modern form-select-compact"
                                >
                                    @foreach($statusLabels as $key => $label)
                                        <option value="{{ $key }}" {{ $ticket->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-sm">
                                <span class="inline-flex items-center rounded-full bg-{{ $ticket->priority_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $ticket->priority_color }}-800">
                                    {{ $ticket->priority_label }}
                                </span>
                            </td>
                            <td class="text-sm">
                                <select
                                    wire:change="assignTicket({{ $ticket->id }}, $event.target.value || null)"
                                    class="form-select-modern form-select-compact"
                                >
                                    <option value="">Unassigned</option>
                                    @foreach($staff as $user)
                                        <option value="{{ $user->id }}" {{ $ticket->assigned_to == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-sm">
                                @if($ticket->is_billable)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                        Billable
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                        Covered
                                    </span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-600">{{ $ticket->created_at->format('M d, Y') }}</td>
                            <td class="actions text-right text-sm">
                                <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-500">
                                No support tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $tickets->links() }}
        </div>
    </div>
</div>

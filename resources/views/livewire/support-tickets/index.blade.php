<div class="space-y-5">
    <!-- Header / actions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Support</div>
            <div class="text-xl font-semibold text-slate-900">Support Tickets</div>
        </div>
        <a href="{{ route('support-tickets.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            New Support Ticket
        </a>
    </div>

    <!-- Status Summary Cards -->
    @if(!empty($statusCounts))
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

            <!-- Total Card -->
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
    @endif

    <!-- Filters -->
    <div class="form-card">
        <div class="form-grid-4">
            <div>
                <label class="form-label-modern">Search</label>
                <input
                    wire:model.live.debounce.250ms="search"
                    type="text"
                    placeholder="Search by subject..."
                    class="form-input"
                />
            </div>

            <div>
                <label class="form-label-modern">Status</label>
                <select
                    wire:model.live="status"
                    class="form-select-modern"
                >
                    <option value="">All statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}">{{ $statusLabels[$s] ?? ucfirst(str_replace('_',' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label-modern">Category</label>
                <select
                    wire:model.live="category"
                    class="form-select-modern"
                >
                    <option value="">All categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label-modern">Billing</label>
                <select
                    wire:model.live="billableFilter"
                    class="form-select-modern"
                >
                    <option value="">All</option>
                    <option value="covered">Covered by Plan</option>
                    <option value="billable">Billable</option>
                </select>
            </div>
        </div>

        <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
            <span wire:loading.inline wire:target="search,status,category,billableFilter">
                Updating...
            </span>
            <span wire:loading.remove wire:target="search,status,category,billableFilter">
                Showing {{ $tickets->total() }} result(s)
            </span>
        </div>
    </div>

    <!-- Table -->
    <div class="form-card p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Billing</th>
                        <th>Created</th>
                        <th class="actions text-right">Actions</th>
                    </tr>
                </thead>

                <!-- Skeleton rows while loading -->
                <tbody wire:loading.delay>
                    @for($i = 0; $i < 8; $i++)
                        <tr>
                            <td class="px-4 py-4"><div class="h-4 w-24 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-72 max-w-[18rem] animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-6 w-24 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-6 w-24 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-6 w-20 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-24 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="ml-auto h-8 w-20 animate-pulse rounded bg-slate-200"></div></td>
                        </tr>
                    @endfor
                </tbody>

                <tbody wire:loading.remove>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="text-sm font-medium text-slate-900">{{ $ticket->ticket_number }}</td>
                            <td class="text-sm text-slate-900">
                                <div class="max-w-[28rem] truncate font-semibold">{{ $ticket->subject }}</div>
                            </td>
                            <td class="text-sm text-slate-600">{{ $ticket->category_label }}</td>
                            <td class="text-sm">
                                <span class="inline-flex items-center rounded-full bg-{{ $ticket->status_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $ticket->status_color }}-800">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td class="text-sm">
                                <span class="inline-flex items-center rounded-full bg-{{ $ticket->priority_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $ticket->priority_color }}-800">
                                    {{ $ticket->priority_label }}
                                </span>
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
                                <a href="{{ route('support-tickets.show', $ticket) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 sm:py-1.5 sm:text-xs">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
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

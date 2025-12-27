<div class="space-y-5">
    <!-- Header / actions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Service Requests</div>
            <div class="text-xl font-semibold text-slate-900">My Requests</div>
        </div>
        <a href="{{ route('requests.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            New Request
        </a>
    </div>

    <!-- Status Summary Cards -->
    @if(!empty($statusCounts))
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @php
                $statusColors = [
                    'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => 'clock'],
                    'in_review' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'eye'],
                    'approved' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200', 'icon' => 'check-circle'],
                    'in_progress' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'border' => 'border-cyan-200', 'icon' => 'refresh-cw'],
                    'on_hold' => ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'icon' => 'pause-circle'],
                    'completed' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'check'],
                    'cancelled' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'icon' => 'x-circle'],
                ];
                $totalRequests = array_sum($statusCounts);
            @endphp

            <!-- Total Card -->
            <button wire:click="$set('status', '')"
                    class="rounded-xl border {{ $status === '' ? 'border-slate-900 ring-2 ring-slate-900' : 'border-slate-200' }} bg-white p-4 text-left transition hover:shadow-md">
                <div class="text-2xl font-bold text-slate-900">{{ $totalRequests }}</div>
                <div class="text-xs font-medium text-slate-500">Total Requests</div>
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
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Search</label>
                <input
                    wire:model.live.debounce.250ms="search"
                    type="text"
                    placeholder="Search by title…"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                />
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Status</label>
                <select
                    wire:model.live="status"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                >
                    <option value="">All statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}">{{ ucfirst(str_replace('_',' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Type</label>
                <select
                    wire:model.live="type"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                >
                    <option value="">All types</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}">{{ ucfirst(str_replace('_',' ', $t)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
            <span wire:loading.inline wire:target="search,status,type,delete">
                Updating…
            </span>
            <span wire:loading.remove wire:target="search,status,type,delete">
                Showing {{ $requests->total() }} result(s)
            </span>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>

                <!-- Skeleton rows while loading -->
                <tbody wire:loading.delay class="divide-y divide-slate-100">
                    @for($i = 0; $i < 8; $i++)
                        <tr>
                            <td class="px-4 py-4"><div class="h-4 w-12 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4">
                                <div class="h-4 w-72 max-w-[18rem] animate-pulse rounded bg-slate-200"></div>
                            </td>
                            <td class="px-4 py-4"><div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-6 w-24 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-6 w-24 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-24 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="ml-auto h-8 w-44 animate-pulse rounded bg-slate-200"></div></td>
                        </tr>
                    @endfor
                </tbody>

                <tbody wire:loading.remove class="divide-y divide-slate-100">
                    @forelse($requests as $request)
                        @php
                            $canEdit = in_array($request->status, ['draft','pending'], true);
                            $canDelete = auth()->user()->isClient()
                                ? $canEdit
                                : true;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">#{{ $request->id }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900">
                                <div class="max-w-[28rem] truncate font-semibold">{{ $request->title }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $request->type_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full bg-slate-900/5 px-2.5 py-1 text-xs font-semibold text-slate-900">
                                    {{ $request->status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full bg-slate-900/5 px-2.5 py-1 text-xs font-semibold text-slate-900">
                                    {{ $request->priority_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $request->created_at->format('M d, Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('requests.show', $request) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 sm:py-1.5 sm:text-xs">
                                        View
                                    </a>
                                    @if($canEdit)
                                        <a href="{{ route('requests.edit', $request) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 sm:py-1.5 sm:text-xs">
                                            Edit
                                        </a>
                                    @else
                                        <span class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-400 sm:py-1.5 sm:text-xs">Edit</span>
                                    @endif

                                    @if($canDelete)
                                        <button
                                            type="button"
                                            wire:click="delete({{ $request->id }})"
                                            onclick="confirm('Delete this request?') || event.stopImmediatePropagation()"
                                            class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 sm:py-1.5 sm:text-xs"
                                            wire:loading.attr="disabled"
                                        >
                                            Delete
                                        </button>
                                    @else
                                        <span class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-400 sm:py-1.5 sm:text-xs">Delete</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">
                                No requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $requests->links() }}
        </div>
    </div>
</div>


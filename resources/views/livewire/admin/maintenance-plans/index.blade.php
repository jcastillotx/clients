<div class="space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Support</div>
            <div class="text-xl font-semibold text-slate-900">Maintenance Plans</div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.support-tickets.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                View Support Tickets
            </a>
            <a href="{{ route('admin.maintenance-plans.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                New Plan
            </a>
        </div>
    </div>

    <!-- Status Summary -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @php
            $statusColors = [
                'active' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
                'paused' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200'],
                'expired' => ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200'],
                'cancelled' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200'],
            ];
            $totalPlans = array_sum($statusCounts);
        @endphp

        <button wire:click="$set('status', '')"
                class="rounded-xl border {{ $status === '' ? 'border-slate-900 ring-2 ring-slate-900' : 'border-slate-200' }} bg-white p-4 text-left transition hover:shadow-md">
            <div class="text-2xl font-bold text-slate-900">{{ $totalPlans }}</div>
            <div class="text-xs font-medium text-slate-500">Total Plans</div>
        </button>

        @foreach($statuses as $key => $label)
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
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Search</label>
                <input
                    wire:model.live.debounce.250ms="search"
                    type="text"
                    placeholder="Search by name..."
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                />
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Status</label>
                <select wire:model.live="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    <option value="">All statuses</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Client</label>
                <select wire:model.live="clientId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    <option value="">All clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Plan Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Monthly Rate</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Included Hours</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Start Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Tickets</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $plan->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $plan->client?->company_name ?? 'N/A' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full bg-{{ $plan->status_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $plan->status_color }}-800">
                                    {{ ucfirst($plan->status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                                {{ $plan->monthly_rate ? '$' . number_format($plan->monthly_rate, 2) : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $plan->included_hours }} hrs</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $plan->start_date?->format('M d, Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $plan->supportTickets->count() }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.maintenance-plans.edit', $plan) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                        Edit
                                    </a>
                                    <button
                                        wire:click="delete({{ $plan->id }})"
                                        onclick="confirm('Delete this maintenance plan?') || event.stopImmediatePropagation()"
                                        class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
                                No maintenance plans found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $plans->links() }}
        </div>
    </div>
</div>

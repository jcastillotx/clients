<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Admin</div>
            <div class="text-xl font-semibold text-slate-900">Activity log</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label class="text-xs font-semibold text-slate-600">Search description</label>
                <input wire:model.debounce.250ms="search" type="text" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="e.g. Updated invoice…" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600">Log</label>
                <select wire:model="logName" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="all">All</option>
                    @foreach($logNames as $ln)
                        <option value="{{ $ln }}">{{ $ln }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600">Event</label>
                <select wire:model="event" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="all">All</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev }}">{{ $ev }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Client ID</label>
                    <input wire:model="clientId" type="number" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="—" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">User ID</label>
                    <input wire:model="userId" type="number" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="—" />
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Log</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activities as $a)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $a->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $a->client_id ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $a->causer?->name ?? $a->user?->name ?? ($a->user_id ?? '—') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $a->event ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $a->log_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900">{{ $a->description }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $a->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">No activity found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $activities->links() }}
        </div>
    </div>
</div>


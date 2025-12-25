<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Contracts</div>
            <div class="text-xl font-semibold text-slate-900">Your contracts</div>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">Status</label>
            <select wire:model="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="draft">Draft</option>
                <option value="pending_signature">Pending Signature</option>
                <option value="terminated">Terminated</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Start Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">End Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Value</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($contracts as $contract)
                        @php
                            $badge = match ($contract->status) {
                                'active' => 'bg-emerald-100 text-emerald-800',
                                'expired' => 'bg-slate-100 text-slate-700',
                                'draft' => 'bg-amber-100 text-amber-800',
                                'pending_signature' => 'bg-amber-100 text-amber-800',
                                'terminated' => 'bg-slate-100 text-slate-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                <a href="{{ route('contracts.show', $contract) }}" class="hover:underline">
                                    {{ $contract->title }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $contract->start_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $contract->end_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">@money($contract->value)</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">
                                    {{ $contract->status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <a href="{{ route('contracts.show', $contract) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                    View Contract
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                No contracts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $contracts->links() }}
        </div>
    </div>
</div>


<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Contracts</div>
            <div class="text-xl font-semibold text-slate-900">Your contracts</div>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">
                Status
                <span class="ml-1 inline-flex items-center text-slate-400" title="Active: currently in effect · Pending Signature: needs your signature · Draft: not finalized · Expired/Terminated: no longer active">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                    </svg>
                </span>
            </label>
            <select wire:model.live="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
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
                <!-- Skeleton rows while loading -->
                <tbody wire:loading.delay class="divide-y divide-slate-100">
                    @for($i = 0; $i < 8; $i++)
                        <tr>
                            <td class="px-4 py-4"><div class="h-4 w-72 max-w-[18rem] animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4 text-right"><div class="ml-auto h-4 w-24 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-6 w-28 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4 text-right"><div class="ml-auto h-8 w-36 animate-pulse rounded bg-slate-200"></div></td>
                        </tr>
                    @endfor
                </tbody>

                <tbody wire:loading.remove class="divide-y divide-slate-100">
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
                                <a href="{{ route('contracts.show', $contract) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 sm:py-1.5 sm:text-xs">
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


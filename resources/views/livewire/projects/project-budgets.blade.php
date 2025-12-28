<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900">Project Budgets</h2>
        <p class="text-sm text-slate-500 mt-1">Track budget and profitability across requests</p>
    </div>

    <!-- Filter Card -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Filter by Request</label>
                <select wire:model="requestId" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                    <option value="">All requests</option>
                    @foreach($requests as $r)
                        <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="recalc" @if(!$requestId) disabled @endif class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                </svg>
                Recalculate Selected
            </button>
        </div>
        <p class="text-xs text-slate-500 mt-3">Profitability is calculated as invoiced − spent (best-effort, based on tracked hourly rates).</p>
    </div>

    <!-- Budgets Table -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Request</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Budget Hrs</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Spent Hrs</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Budget $</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Spent $</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Invoiced $</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Margin $</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($budgets as $b)
                        @php
                            $inv = (float) ($invoiceTotals[$b->request_id] ?? 0);
                            $spent = (float) ($b->spent_amount ?? 0);
                            $margin = $inv - $spent;
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-900">#{{ $b->request_id }}</div>
                                <div class="text-xs text-slate-500 truncate max-w-[200px]">{{ $b->request?->title }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-900 text-right font-medium">{{ $b->budget_hours ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900 text-right">{{ $b->spent_hours ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900 text-right">${{ number_format((float)($b->budget_amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900 text-right">${{ number_format($spent, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900 text-right">${{ number_format($inv, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold {{ $margin >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $margin >= 0 ? '+' : '' }}${{ number_format($margin, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($b->is_exceeded)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                        Exceeded
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        On Track
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="text-sm text-slate-500">No budgets configured yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Invoices</div>
            <div class="text-xl font-semibold text-slate-900">Your invoices</div>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">Status</label>
            <select wire:model.live="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                <option value="all">All</option>
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Invoice #</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Due Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Balance Due</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <!-- Skeleton rows while loading -->
                <tbody wire:loading.delay class="divide-y divide-slate-100">
                    @for($i = 0; $i < 8; $i++)
                        <tr>
                            <td class="px-4 py-4"><div class="h-4 w-40 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4 text-right"><div class="ml-auto h-4 w-20 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4"><div class="h-6 w-24 animate-pulse rounded-full bg-slate-200"></div></td>
                            <td class="px-4 py-4 text-right"><div class="ml-auto h-4 w-20 animate-pulse rounded bg-slate-200"></div></td>
                            <td class="px-4 py-4 text-right"><div class="ml-auto h-8 w-40 animate-pulse rounded bg-slate-200"></div></td>
                        </tr>
                    @endfor
                </tbody>

                <tbody wire:loading.remove class="divide-y divide-slate-100">
                    @forelse($invoices as $invoice)
                        @php
                            $badge = match (true) {
                                $invoice->isPaid() => 'bg-emerald-100 text-emerald-800',
                                $invoice->isOverdue() => 'bg-rose-100 text-rose-800',
                                $invoice->canBePaid() => 'bg-amber-100 text-amber-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                            $payable = $invoice->canBePaid() && $invoice->balance_due > 0;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-900">
                                <a href="{{ route('invoices.show', $invoice) }}" class="hover:underline">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">@money($invoice->amount)</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">
                                    {{ $invoice->status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">@money($invoice->balance_due)</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 sm:py-1.5 sm:text-xs">
                                        View
                                    </a>
                                    @if($payable)
                                        <a href="{{ route('payments.show', $invoice) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 sm:py-1.5 sm:text-xs" title="Payments are processed securely via Stripe.">
                                            Pay Now
                                        </a>
                                    @else
                                        <span class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-400 sm:py-1.5 sm:text-xs" title="This invoice can't be paid online right now.">
                                            Pay Now
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>


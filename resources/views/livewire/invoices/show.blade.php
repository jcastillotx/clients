@php
    $invoice = $invoice;
    $client = $invoice->client;
    $payable = $invoice->canBePaid() && $invoice->balance_due > 0;
    $badge = match (true) {
        $invoice->isPaid() => 'bg-emerald-100 text-emerald-800',
        $invoice->isOverdue() => 'bg-rose-100 text-rose-800',
        $invoice->canBePaid() => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Invoice</div>
            <div class="text-xl font-semibold text-slate-900">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('invoices.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Back
            </a>
            <a href="{{ route('invoices.download', $invoice) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Download PDF
            </a>
            @if($payable)
                <a href="{{ route('payments.show', $invoice) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Pay Now
                </a>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <div class="text-sm text-slate-500">Issue date</div>
                <div class="text-sm font-semibold text-slate-900">{{ $invoice->issue_date?->format('M d, Y') ?? '—' }}</div>
                <div class="mt-3 text-sm text-slate-500">Due date</div>
                <div class="text-sm font-semibold text-slate-900">
                    {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                    @if($invoice->isOverdue())
                        <span class="ml-2 text-xs font-semibold text-rose-700">(Overdue)</span>
                    @endif
                </div>
            </div>

            <div class="text-right">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">
                    {{ $invoice->status_label }}
                </span>
                <div class="mt-3 text-sm text-slate-500">Total</div>
                <div class="text-2xl font-semibold text-slate-900">@money($invoice->amount)</div>
                @if($invoice->balance_due > 0 && $invoice->balance_due < (float) $invoice->amount)
                    <div class="mt-1 text-sm text-slate-500">
                        Balance due: <span class="font-semibold text-slate-900">@money($invoice->balance_due)</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Billing info -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold text-slate-900">Billing information</div>
            <div class="mt-4 space-y-1 text-sm text-slate-700">
                <div class="font-semibold text-slate-900">{{ $client?->company_name }}</div>
                <div>{{ $client?->contact_name }}</div>
                @if($client?->address)
                    <div>{{ $client->address }}</div>
                @endif
                @if($client && ($client->city || $client->state || $client->zip_code))
                    <div>{{ $client->city }}, {{ $client->state }} {{ $client->zip_code }}</div>
                @endif
                <div class="pt-2 text-slate-500">{{ $client?->email }}</div>
            </div>
        </div>

        <!-- Line items -->
        <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <div class="text-sm font-semibold text-slate-900">Line items</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Description</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Qty</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Unit price</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-5 py-3 text-sm text-slate-900">{{ $item->description }}</td>
                                <td class="px-5 py-3 text-center text-sm text-slate-700">{{ number_format((float) $item->quantity, 2) }}</td>
                                <td class="px-5 py-3 text-right text-sm text-slate-700">@money($item->unit_price)</td>
                                <td class="px-5 py-3 text-right text-sm font-semibold text-slate-900">@money($item->total)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                <div class="ml-auto w-full max-w-sm space-y-2 text-sm">
                    <div class="flex items-center justify-between text-slate-700">
                        <span>Subtotal</span>
                        <span class="font-semibold text-slate-900">@money($invoice->subtotal)</span>
                    </div>
                    @if((float) $invoice->tax_amount > 0)
                        <div class="flex items-center justify-between text-slate-700">
                            <span>Tax ({{ $invoice->tax_rate }}%)</span>
                            <span class="font-semibold text-slate-900">@money($invoice->tax_amount)</span>
                        </div>
                    @endif
                    @if((float) $invoice->discount > 0)
                        <div class="flex items-center justify-between text-slate-700">
                            <span>Discount</span>
                            <span class="font-semibold text-rose-700">-@money($invoice->discount)</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-slate-900">
                        <span class="text-base font-semibold">Total</span>
                        <span class="text-base font-semibold">@money($invoice->amount)</span>
                    </div>
                </div>

                @if($invoice->notes)
                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</div>
                        <div class="mt-1 whitespace-pre-wrap">{{ $invoice->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Payment history -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-900">Payment history</div>
            <div class="text-xs text-slate-500">{{ $invoice->payments->count() }}</div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Method</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Transaction</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoice->payments as $payment)
                        @php
                            $pBadge = match ($payment->status) {
                                'succeeded' => 'bg-emerald-100 text-emerald-800',
                                'failed' => 'bg-rose-100 text-rose-800',
                                'processing' => 'bg-amber-100 text-amber-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">@money($payment->amount)</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ ucfirst($payment->payment_method) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $pBadge }}">
                                    {{ $payment->status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                <code class="rounded bg-slate-100 px-2 py-1">{{ $payment->transaction_id ?? '-' }}</code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No payments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>


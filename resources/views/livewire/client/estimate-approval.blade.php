<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Estimate approval</div>
            <div class="text-xl font-semibold text-slate-900">{{ $request->title }}</div>
            <div class="mt-1 text-sm text-slate-600">
                Client: <span class="font-semibold text-slate-900">{{ $request->client?->company_name }}</span>
            </div>
        </div>
        @if($contract)
            <a href="{{ route('contracts.show', $contract) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                View / Sign SOW
            </a>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="text-sm font-semibold text-slate-900">Scope items</div>
            <div class="mt-1 text-xs text-slate-500">Optional items can be toggled. Totals update automatically.</div>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($tasks as $i => $t)
                @php
                    $optional = (bool) ($t['optional'] ?? false);
                    $included = (bool) ($t['included'] ?? true);
                @endphp
                <div class="px-5 py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">
                                {{ $t['name'] ?? 'Task' }}
                                @if($optional)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Optional</span>
                                @endif
                            </div>
                            @if(!empty($t['description']))
                                <div class="mt-1 text-sm text-slate-600">{{ $t['description'] }}</div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-xs text-slate-500">
                                Hours (L/M/H): {{ number_format((float)($t['hours_low'] ?? 0), 1) }}/{{ number_format((float)($t['hours_mid'] ?? 0), 1) }}/{{ number_format((float)($t['hours_high'] ?? 0), 1) }}
                            </div>
                            @if($optional)
                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                    <input type="checkbox" wire:model.live="tasks.{{ $i }}.included" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900" />
                                    <span class="font-semibold text-slate-800">{{ $included ? 'Included' : 'Excluded' }}</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold text-slate-900">Message (optional)</div>
            <div class="mt-1 text-xs text-slate-500">Ask for changes or clarifications.</div>
            <textarea wire:model="message" rows="4" class="mt-3 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Write a note…"></textarea>
            <div class="mt-3 flex flex-wrap gap-2">
                <button wire:click="requestChanges" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                    Request modifications
                </button>
                <button wire:click="approve" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                    Approve & Sign
                </button>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold text-slate-900">Investment</div>
            <div class="mt-4 space-y-2 text-sm text-slate-700">
                <div class="flex items-center justify-between">
                    <span>Low</span>
                    <span class="font-semibold">${{ number_format((float)($pricing['totals']['low']['total'] ?? 0), 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Mid</span>
                    <span class="font-semibold">${{ number_format((float)($pricing['totals']['mid']['total'] ?? 0), 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>High</span>
                    <span class="font-semibold">${{ number_format((float)($pricing['totals']['high']['total'] ?? 0), 2) }}</span>
                </div>
            </div>
            <div class="mt-3 text-xs text-slate-500">
                Totals include markup and contingency.
            </div>
        </div>
    </div>
</div>


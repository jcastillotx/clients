<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Activity</div>
            <div class="text-xl font-semibold text-slate-900">Recent activity</div>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">Filter</label>
            <select wire:model="type" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                <option value="all">All</option>
                <option value="requests">Requests</option>
                <option value="invoices">Invoices</option>
                <option value="contracts">Contracts</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse($activities as $activity)
                @php
                    $actor = $activity->causer?->name ?? $activity->user?->name ?? 'System';
                    $action = $activity->event ?? 'updated';

                    $subjectLabel = 'Record';
                    if ($activity->subject) {
                        $cls = class_basename($activity->subject_type ?? get_class($activity->subject));
                        $id = $activity->subject->id ?? $activity->subject_id;

                        if ($cls === 'Request') $subjectLabel = "Request #{$id}";
                        elseif ($cls === 'Invoice') $subjectLabel = "Invoice " . ($activity->subject->invoice_number ?? "#{$id}");
                        elseif ($cls === 'Contract') $subjectLabel = "Contract #{$id}";
                        elseif ($cls === 'Client') $subjectLabel = "Client #{$id}";
                        elseif ($cls === 'Payment') $subjectLabel = "Payment #{$id}";
                        else $subjectLabel = "{$cls} #{$id}";
                    } elseif ($activity->subject_id) {
                        $subjectLabel = class_basename((string) $activity->subject_type) . ' #' . $activity->subject_id;
                    }
                @endphp

                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900 truncate">
                                {{ $actor }} {{ $action }} {{ $subjectLabel }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $activity->created_at?->diffForHumans() }}
                                @if($activity->ip_address)
                                    · IP {{ $activity->ip_address }}
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                {{ $activity->log_name ?? 'default' }}
                            </span>
                        </div>
                    </div>

                    @if(!empty($activity->changes))
                        <details class="mt-3">
                            <summary class="cursor-pointer text-xs font-semibold text-slate-700 hover:underline">
                                View changes
                            </summary>
                            <div class="mt-2 rounded-xl bg-slate-50 p-3 text-xs text-slate-700 overflow-x-auto">
                                <pre style="white-space:pre-wrap;">{{ json_encode($activity->changes, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </details>
                    @endif
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">
                    No activity yet.
                </div>
            @endforelse
        </div>

        @if(method_exists($activities, 'links'))
            <div class="border-t border-slate-200 bg-white px-4 py-3">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>


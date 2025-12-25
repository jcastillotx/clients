<div class="space-y-6">
    <div wire:loading.flex class="fixed inset-0 z-50 items-center justify-center bg-slate-900/20 backdrop-blur-sm" aria-label="Loading" style="display:none;">
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
            <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm font-medium text-slate-700">Loading request…</span>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Request #{{ $request->id }}</div>
            <div class="text-xl font-semibold text-slate-900">{{ $request->title }}</div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('requests.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Back
            </a>
            @if(in_array($request->status, ['draft','pending'], true))
                <a href="{{ route('requests.edit', $request) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Edit Request
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="text-sm text-slate-500">Type</div>
                        <div class="text-sm font-semibold text-slate-900">{{ $request->type_label }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full bg-slate-900/5 px-2.5 py-1 text-xs font-semibold text-slate-900">
                            {{ $request->status_label }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-slate-900/5 px-2.5 py-1 text-xs font-semibold text-slate-900">
                            {{ $request->priority_label }} priority
                        </span>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <div class="text-xs font-semibold text-slate-500">Created</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $request->created_at->format('M d, Y h:i A') }}</div>
                        <div class="mt-0.5 text-xs text-slate-500">By {{ $request->creator?->name ?? 'Unknown' }}</div>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <div class="text-xs font-semibold text-slate-500">Assigned to</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $request->assignee?->name ?? 'Unassigned' }}</div>
                        @if($request->due_date)
                            <div class="mt-0.5 text-xs text-slate-500">
                                Due {{ $request->due_date->format('M d, Y') }}
                                @if($request->isOverdue())
                                    <span class="ml-1 font-semibold text-rose-700">(Overdue)</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-5">
                    <div class="text-sm font-semibold text-slate-900">Description</div>
                    <div class="mt-2 whitespace-pre-wrap rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                        {{ $request->description }}
                    </div>
                </div>
            </div>

            <!-- Attachments -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Attachments</h2>
                    <span class="text-xs text-slate-500">{{ $request->attachments->count() }}</span>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @forelse($request->attachments as $attachment)
                        <a href="{{ $attachment->url }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900/5 text-slate-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l3-3m-3 3l-3-3M6 20h12" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-900 group-hover:underline">{{ $attachment->original_filename }}</div>
                                <div class="text-xs text-slate-500">{{ $attachment->human_file_size }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 sm:col-span-2">
                            No attachments uploaded.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Comments -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Comments</h2>
                    <span class="text-xs text-slate-500">Live</span>
                </div>
                <div class="mt-4">
                    <livewire:requests.request-comments :request="$request" lazy />
                </div>
            </div>
        </div>

        <!-- Timeline / history -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Status history</h2>

                <div class="mt-4 space-y-3">
                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <div class="text-xs font-semibold text-slate-500">Created</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $request->created_at->format('M d, Y h:i A') }}</div>
                    </div>

                    @if($request->started_at)
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold text-slate-500">Started</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ $request->started_at->format('M d, Y h:i A') }}</div>
                        </div>
                    @endif

                    @if($request->completed_at)
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <div class="text-xs font-semibold text-slate-500">Completed</div>
                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ $request->completed_at->format('M d, Y h:i A') }}</div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 space-y-3">
                    @forelse($statusHistory as $log)
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-900">{{ $log->event ?? 'event' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->created_at?->diffForHumans() }}</div>
                            </div>
                            <div class="mt-1 text-sm text-slate-700">{{ $log->description }}</div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                            No status history yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>


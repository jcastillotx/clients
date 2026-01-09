<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('support-tickets.index') }}" class="hover:text-slate-700">Support Tickets</a>
                <span>/</span>
                <span>{{ $ticket->ticket_number }}</span>
            </div>
            <div class="text-xl font-semibold text-slate-900">{{ $ticket->subject }}</div>
        </div>
        <a href="{{ route('support-tickets.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Back to Tickets
        </a>
    </div>

    <!-- Success/Error Messages -->
    @if(session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div class="text-sm text-emerald-800">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Details -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Description</h3>
                <div class="prose prose-sm max-w-none text-slate-700">
                    {!! nl2br(e($ticket->description)) !!}
                </div>
            </div>

            <!-- Comments Section -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Comments</h3>

                <!-- Add Comment Form -->
                <div class="mb-6">
                    <textarea
                        wire:model="newComment"
                        rows="3"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                        placeholder="Add a comment..."
                    ></textarea>
                    @error('newComment')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                    <div class="mt-2 flex justify-end">
                        <button
                            wire:click="addComment"
                            wire:loading.attr="disabled"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            <span wire:loading.remove wire:target="addComment">Add Comment</span>
                            <span wire:loading wire:target="addComment">Posting...</span>
                        </button>
                    </div>
                </div>

                <!-- Comments List -->
                <div class="space-y-4">
                    @forelse($ticket->publicComments as $comment)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="h-8 w-8 rounded-full bg-slate-300 flex items-center justify-center text-xs font-semibold text-slate-600">
                                    {{ substr($comment->user?->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $comment->user?->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            <div class="text-sm text-slate-700">
                                {!! nl2br(e($comment->comment)) !!}
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-sm text-slate-500">
                            No comments yet. Be the first to comment!
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Ticket Info -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Ticket Details</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Ticket #</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $ticket->ticket_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Status</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full bg-{{ $ticket->status_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $ticket->status_color }}-800">
                                {{ $ticket->status_label }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Priority</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full bg-{{ $ticket->priority_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $ticket->priority_color }}-800">
                                {{ $ticket->priority_label }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Category</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $ticket->category_label }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Created</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $ticket->created_at->format('M d, Y') }}</dd>
                    </div>
                    @if($ticket->assignedTo)
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Assigned To</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $ticket->assignedTo->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Billing Info -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Billing Status</h3>
                @if($ticket->is_billable)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-semibold text-amber-800">Billable</span>
                        </div>
                        <p class="mt-2 text-xs text-amber-700">This ticket is not covered by a maintenance plan and will be billed separately.</p>
                        @if($ticket->hourly_rate)
                            <p class="mt-1 text-xs text-amber-700">Rate: ${{ number_format($ticket->hourly_rate, 2) }}/hour</p>
                        @endif
                        @if($ticket->actual_hours)
                            <p class="mt-1 text-xs text-amber-700">Time logged: {{ $ticket->actual_hours }} hours</p>
                            <p class="mt-1 text-sm font-semibold text-amber-800">Estimated cost: ${{ number_format($ticket->billable_amount, 2) }}</p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-semibold text-emerald-800">Covered by Plan</span>
                        </div>
                        @if($ticket->maintenancePlan)
                            <p class="mt-2 text-xs text-emerald-700">Plan: {{ $ticket->maintenancePlan->name }}</p>
                        @endif
                        <p class="mt-1 text-xs text-emerald-700">This ticket is covered under your maintenance plan at no additional charge.</p>
                    </div>
                @endif
            </div>

            <!-- Activity History -->
            @if($statusHistory->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">Activity</h3>
                    <div class="space-y-3">
                        @foreach($statusHistory as $activity)
                            <div class="flex items-start gap-3">
                                <div class="mt-1 h-2 w-2 rounded-full bg-slate-300"></div>
                                <div>
                                    <div class="text-xs text-slate-600">{{ $activity->description }}</div>
                                    <div class="text-xs text-slate-400">{{ $activity->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

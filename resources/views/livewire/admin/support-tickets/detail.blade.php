<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('admin.support-tickets.index') }}" class="hover:text-slate-700">Support Tickets</a>
                <span>/</span>
                <span>{{ $ticket->ticket_number }}</span>
            </div>
            <div class="text-xl font-semibold text-slate-900">{{ $ticket->subject }}</div>
        </div>
        <a href="{{ route('admin.support-tickets.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Back to Tickets
        </a>
    </div>

    <!-- Success Messages -->
    @if(session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-sm text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Description -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Description</h3>
                <div class="prose prose-sm max-w-none text-slate-700">
                    {!! nl2br(e($ticket->description)) !!}
                </div>

                <div class="mt-4 pt-4 border-t border-slate-200">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-500">Category</dt>
                            <dd class="font-medium text-slate-900">{{ $ticket->category_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Created by</dt>
                            <dd class="font-medium text-slate-900">{{ $ticket->creator?->name ?? 'Unknown' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Comments -->
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
                    <div class="mt-2 flex items-center justify-between">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="isInternal" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            <span class="text-xs text-slate-600">Internal note (not visible to client)</span>
                        </label>
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
                    @forelse($ticket->comments as $comment)
                        <div class="rounded-xl border {{ $comment->is_internal ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }} p-4">
                            @if($comment->is_internal)
                                <div class="mb-2 text-xs font-semibold text-amber-600">Internal Note</div>
                            @endif
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
                            No comments yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Ticket Management -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Ticket Management</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($statuses as $key => $label)
                                <button
                                    wire:click="updateStatus('{{ $key }}')"
                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $ticket->status === $key ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Assigned To</label>
                        <select wire:model.live="assignedTo" wire:change="updateAssignment" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                            <option value="">Unassigned</option>
                            @foreach($staff as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Actual Hours</label>
                        <div class="mt-1 flex gap-2">
                            <input
                                type="number"
                                step="0.25"
                                min="0"
                                wire:model="actualHours"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                placeholder="0.00"
                            />
                            <button wire:click="updateHours" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Info -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Ticket Details</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Ticket #</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $ticket->ticket_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Client</dt>
                        <dd class="text-sm font-medium text-slate-900">
                            <a href="{{ route('admin.clients.show', $ticket->client) }}" class="text-blue-600 hover:underline">
                                {{ $ticket->client?->company_name ?? 'N/A' }}
                            </a>
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
                        <dt class="text-sm text-slate-500">Created</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $ticket->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                    @if($ticket->first_response_at)
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">First Response</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $ticket->first_response_at->format('M d, Y H:i') }}</dd>
                        </div>
                    @endif
                    @if($ticket->resolved_at)
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Resolved</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $ticket->resolved_at->format('M d, Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Billing Info -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Billing</h3>
                @if($ticket->is_billable)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-amber-800">Billable</span>
                        </div>
                        @if($ticket->hourly_rate)
                            <p class="mt-2 text-xs text-amber-700">Rate: ${{ number_format($ticket->hourly_rate, 2) }}/hour</p>
                        @endif
                        @if($ticket->actual_hours)
                            <p class="mt-1 text-xs text-amber-700">Time logged: {{ $ticket->actual_hours }} hours</p>
                            <p class="mt-1 text-sm font-semibold text-amber-800">Total: ${{ number_format($ticket->billable_amount, 2) }}</p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-emerald-800">Covered by Plan</span>
                        </div>
                        @if($ticket->maintenancePlan)
                            <p class="mt-2 text-xs text-emerald-700">Plan: {{ $ticket->maintenancePlan->name }}</p>
                            <a href="{{ route('admin.maintenance-plans.edit', $ticket->maintenancePlan) }}" class="mt-1 inline-block text-xs text-emerald-700 underline hover:no-underline">
                                View Plan
                            </a>
                        @endif
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

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-sm text-slate-500">Maintenance Plans</div>
            <div class="text-xl font-semibold text-slate-900">Edit: {{ $plan->name }}</div>
        </div>
        <a href="{{ route('admin.maintenance-plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Back
        </a>
    </div>

    @if(session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-sm text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Client <span class="text-rose-600">*</span></label>
                    <select wire:model="clientId" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                        <option value="">Select a client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                    @error('clientId')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Plan Name <span class="text-rose-600">*</span></label>
                    <input wire:model="name" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="e.g., Monthly Maintenance" />
                    @error('name')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-600">Description</label>
                    <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Description of what this plan covers"></textarea>
                    @error('description')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Status <span class="text-rose-600">*</span></label>
                        <select wire:model="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Monthly Rate ($)</label>
                        <input wire:model="monthlyRate" type="number" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="0.00" />
                        @error('monthlyRate')
                            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Included Hours <span class="text-rose-600">*</span></label>
                        <input wire:model="includedHours" type="number" min="0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="0" />
                        @error('includedHours')
                            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Overage Hourly Rate ($)</label>
                        <input wire:model="hourlyRateOverage" type="number" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="0.00" />
                        @error('hourlyRateOverage')
                            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">Start Date <span class="text-rose-600">*</span></label>
                        <input wire:model="startDate" type="date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
                        @error('startDate')
                            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-600">End Date</label>
                        <input wire:model="endDate" type="date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
                        @error('endDate')
                            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="pt-4 flex items-center gap-3 border-t border-slate-200">
                    <button wire:click="save" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Save Changes
                    </button>
                    <button
                        wire:click="delete"
                        onclick="confirm('Delete this maintenance plan? This cannot be undone.') || event.stopImmediatePropagation()"
                        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                    >
                        Delete Plan
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Plan Info -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Plan Summary</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Status</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full bg-{{ $plan->status_color }}-100 px-2.5 py-1 text-xs font-semibold text-{{ $plan->status_color }}-800">
                                {{ ucfirst($plan->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Created</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $plan->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Support Tickets</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $plan->supportTickets->count() }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Recent Tickets -->
            @if($plan->supportTickets->count() > 0)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">Recent Tickets</h3>
                    <div class="space-y-3">
                        @foreach($plan->supportTickets->take(5) as $ticket)
                            <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                                <div class="text-xs font-medium text-slate-500">{{ $ticket->ticket_number }}</div>
                                <div class="text-sm font-semibold text-slate-900 truncate">{{ $ticket->subject }}</div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center rounded-full bg-{{ $ticket->status_color }}-100 px-2 py-0.5 text-xs font-medium text-{{ $ticket->status_color }}-800">
                                        {{ $ticket->status_label }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

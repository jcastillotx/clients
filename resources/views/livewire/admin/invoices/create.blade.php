<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-2xl font-semibold text-slate-900">Create Invoice</h1>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
            Back
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Main Form Column -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Invoice Type Toggle -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex rounded-xl bg-slate-100 p-1">
                    <button type="button" 
                            wire:click="$set('is_recurring', false)"
                            class="flex-1 flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all {{ !$is_recurring ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                        </svg>
                        One-Time Invoice
                    </button>
                    <button type="button" 
                            wire:click="$set('is_recurring', true)"
                            class="flex-1 flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all {{ $is_recurring ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Recurring Invoice
                    </button>
                </div>
            </div>

            <!-- Client & Template Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Client & Template</h2>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client <span class="text-rose-500">*</span></label>
                        <select wire:model.live="client_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <option value="">Select a client…</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Template</label>
                        <select wire:model.live="template" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @foreach($templates as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('template')
                            <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Recurring Schedule Card (only shown when recurring) -->
            @if($is_recurring)
                <div class="rounded-2xl border-2 border-blue-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-blue-200 bg-blue-50 flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        <h2 class="text-base font-semibold text-blue-900">Recurring Schedule</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Schedule Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model.live="recurring_name" placeholder="e.g., Monthly Retainer - Acme Corp" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <p class="mt-1.5 text-xs text-slate-500">Internal name to identify this recurring invoice schedule.</p>
                            @error('recurring_name')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Frequency <span class="text-rose-500">*</span></label>
                                <select wire:model.live="recurring_frequency" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                    @foreach($frequencyOptions as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('recurring_frequency')
                                    <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            @if(in_array($recurring_frequency, ['monthly', 'quarterly', 'yearly']))
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Day of Month</label>
                                    <select wire:model.live="recurring_day_of_month" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                        <option value="">Same as start date</option>
                                        @for($d = 1; $d <= 28; $d++)
                                            <option value="{{ $d }}">{{ $d }}{{ $d == 1 ? 'st' : ($d == 2 ? 'nd' : ($d == 3 ? 'rd' : 'th')) }}</option>
                                        @endfor
                                    </select>
                                    <p class="mt-1.5 text-xs text-slate-500">Generate invoice on this day each period.</p>
                                </div>
                            @endif

                            @if(in_array($recurring_frequency, ['weekly', 'biweekly']))
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Day of Week</label>
                                    <select wire:model.live="recurring_day_of_week" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                        <option value="">Same as start date</option>
                                        @foreach($dayOfWeekOptions as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Start Date <span class="text-rose-500">*</span></label>
                                <input type="date" wire:model.live="recurring_start_date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                <p class="mt-1.5 text-xs text-slate-500">First invoice generated on this date.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">End Date</label>
                                <input type="date" wire:model.live="recurring_end_date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                <p class="mt-1.5 text-xs text-slate-500">Leave empty for indefinite.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Max Occurrences</label>
                                <input type="number" wire:model.live="recurring_occurrences_limit" min="1" max="999" placeholder="Unlimited" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                <p class="mt-1.5 text-xs text-slate-500">Stop after this many invoices.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Payment Terms (Days)</label>
                                <input type="number" wire:model.live="recurring_payment_terms_days" min="0" max="365" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                <p class="mt-1.5 text-xs text-slate-500">Due date = Issue date + this many days.</p>
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" wire:model.live="recurring_auto_send" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                    <span class="text-sm text-slate-700">Auto-send to client when generated</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Invoice Details Card (only for one-time) -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-base font-semibold text-slate-900">Invoice Details</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Invoice Number</label>
                            <div class="flex">
                                <label class="flex items-center gap-2 px-3 py-2.5 rounded-l-xl border border-r-0 border-slate-300 bg-slate-50 cursor-pointer">
                                    <input type="checkbox" wire:model.live="autoNumber" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                    <span class="text-xs font-medium text-slate-700">Auto</span>
                                </label>
                                <input type="text" wire:model.live="invoice_number" placeholder="INV-YYYYMM-0001" class="flex-1 rounded-r-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled($autoNumber)>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500">If Auto is enabled, the number is generated on save.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Issue Date <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model.live="issue_date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @error('issue_date')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Due Date <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model.live="due_date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @error('due_date')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endif

            <!-- Related Links Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Related Links</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Optional: Link this invoice to a request or contract</p>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Related Request</label>
                        <select wire:model.live="request_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$client_id)>
                            <option value="">None</option>
                            @foreach($requests as $r)
                                <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->title }} ({{ $r->status }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Related Contract</label>
                        <select wire:model.live="contract_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$client_id)>
                            <option value="">None</option>
                            @foreach($contracts as $c)
                                <option value="{{ $c->id }}">#{{ $c->id }} · {{ $c->title }} ({{ $c->status }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Line Items Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Line Items</h2>
                    <button type="button" wire:click="addItem" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Line
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="min-width: 200px;">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 180px;">Service</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 100px;">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 120px;">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 120px;">Total</th>
                                <th class="px-4 py-3" style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($items as $i => $row)
                                <tr>
                                    <td class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.250ms="items.{{ $i }}.description" placeholder="Design work, monthly retainer, …" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                        @error("items.$i.description")
                                            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3">
                                        <select wire:model.live="items.{{ $i }}.feature_key" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                            <option value="">None</option>
                                            @foreach($featureOptions as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0.01" wire:model.live.debounce.250ms="items.{{ $i }}.quantity" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 text-right focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex">
                                            <span class="inline-flex items-center px-3 py-2 rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 text-sm text-slate-500">$</span>
                                            <input type="number" step="0.01" min="0" wire:model.live.debounce.250ms="items.{{ $i }}.unit_price" class="flex-1 rounded-r-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 text-right focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-slate-900">${{ number_format((float)($row['total'] ?? 0), 2) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" wire:click="removeItem({{ $i }})" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Remove line">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center">
                                        <p class="text-sm text-slate-500">No line items yet. Click "Add Line" to get started.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(count($featureOptions) > 0)
                    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50">
                        <div class="flex items-start gap-2 text-xs text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <span>Selecting a Service/Feature will enable that feature for the client when the invoice is paid.</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Notes & Terms Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Notes & Terms</h2>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Notes</label>
                        <textarea wire:model.live.debounce.350ms="notes" rows="4" placeholder="Additional notes for the client…" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Terms</label>
                        <textarea wire:model.live.debounce.350ms="terms" rows="4" placeholder="Payment terms and conditions…" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="xl:col-span-1">
            <div class="sticky top-4 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Summary</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tax Rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" wire:model.live="tax_rate" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Discount ($)</label>
                            <input type="number" step="0.01" min="0" wire:model.live="discount" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-medium text-slate-900">${{ number_format((float)$subtotal, 2) }}</span>
                        </div>
                        @if((float)$discount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Discount</span>
                                <span class="font-medium text-rose-600">-${{ number_format((float)$discount, 2) }}</span>
                            </div>
                        @endif
                        @if((float)$tax_rate > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Tax ({{ number_format((float)$tax_rate, 2) }}%)</span>
                                <span class="font-medium text-slate-900">${{ number_format((float)$taxAmount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-slate-200">
                            <span class="font-semibold text-slate-900">{{ $is_recurring ? 'Per Invoice' : 'Total' }}</span>
                            <span class="text-2xl font-bold text-slate-900">${{ number_format((float)$total, 2) }}</span>
                        </div>
                    </div>

                    @if($is_recurring)
                        <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-blue-900">Recurring Invoice</p>
                                    <p class="text-xs text-blue-700 mt-1">
                                        Invoices will be automatically generated {{ strtolower($frequencyOptions[$recurring_frequency] ?? $recurring_frequency) }}
                                        @if($recurring_start_date)
                                            starting {{ \Carbon\Carbon::parse($recurring_start_date)->format('M j, Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 space-y-2">
                    @if($is_recurring)
                        <button type="button" wire:click="saveRecurring" wire:loading.attr="disabled" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            <span wire:loading.remove wire:target="saveRecurring">Create Recurring Schedule</span>
                            <span wire:loading wire:target="saveRecurring">Creating…</span>
                        </button>
                    @else
                        <button type="button" wire:click="sendToClient" wire:loading.attr="disabled" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                            <span wire:loading.remove wire:target="sendToClient">Send to Client</span>
                            <span wire:loading wire:target="sendToClient">Sending…</span>
                        </button>
                        <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            <span wire:loading.remove wire:target="saveDraft">Save as Draft</span>
                            <span wire:loading wire:target="saveDraft">Saving…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

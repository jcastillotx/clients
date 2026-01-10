<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-2xl font-semibold text-slate-900">Edit {{ $invoice->invoice_number }}</h1>
            <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                <span>{{ $invoice->client?->company_name }}</span>
                <span>·</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ match($invoice->status) {
                    'paid' => 'bg-emerald-100 text-emerald-800',
                    'sent', 'pending' => 'bg-blue-100 text-blue-800',
                    'overdue' => 'bg-rose-100 text-rose-800',
                    'voided' => 'bg-slate-100 text-slate-800',
                    default => 'bg-amber-100 text-amber-800'
                } }}">
                    {{ $invoiceStatuses[$invoice->status] ?? $invoice->status }}
                </span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                Preview PDF
            </a>
            <a href="{{ route('admin.invoices.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                Back
            </a>
        </div>
    </div>

    @if(!$editable)
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-amber-800">
                    This invoice is <strong>{{ $invoiceStatuses[$invoice->status] ?? $invoice->status }}</strong> and is not editable.
                </p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Main Form Column -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Invoice Details Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-slate-900">Invoice Details</h2>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" 
                                wire:click="sendOrResend" 
                                wire:loading.attr="disabled" 
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                            {{ in_array($invoice->status, ['draft']) ? 'Send to Client' : 'Resend Email' }}
                        </button>
                        <button type="button" 
                                wire:click="voidInvoice" 
                                wire:loading.attr="disabled" 
                                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                            </svg>
                            Void
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-5">
                    <!-- Dates & Template -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Issue Date</label>
                            <input type="date" wire:model.live="issue_date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
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
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Due Date</label>
                            <input type="date" wire:model.live="due_date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                            @error('due_date')
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
                            <select wire:model.live="template" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
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

                    <!-- Related Links -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Related Request</label>
                            <select wire:model.live="request_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                <option value="">None</option>
                                @foreach($requests as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->title }} ({{ $r->status }})</option>
                                @endforeach
                            </select>
                            @error('request_id')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Related Contract</label>
                            <select wire:model.live="contract_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                <option value="">None</option>
                                @foreach($contracts as $c)
                                    <option value="{{ $c->id }}">#{{ $c->id }} · {{ $c->title }} ({{ $c->status }})</option>
                                @endforeach
                            </select>
                            @error('contract_id')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Line Items Section -->
                    <div class="border-t border-slate-200 pt-5">
                        <div class="flex items-center justify-between mb-4">
                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Line Items</label>
                            @if($editable)
                                <button type="button" wire:click="addItem" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Add Line
                                </button>
                            @endif
                        </div>
                        <div class="overflow-x-auto -mx-6">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-y border-slate-200 bg-slate-50">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="min-width: 200px;">Description</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 180px;">Service</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 100px;">Qty</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 130px;">Unit Price</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider" style="width: 110px;">Total</th>
                                        <th class="px-6 py-3" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($items as $i => $row)
                                        <tr>
                                            <td class="px-6 py-3">
                                                <input type="text" wire:model.live.debounce.250ms="items.{{ $i }}.description" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                                @error("items.$i.description")
                                                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-3">
                                                <select wire:model.live="items.{{ $i }}.feature_key" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                                    <option value="">None</option>
                                                    @foreach($featureOptions as $k => $label)
                                                        <option value="{{ $k }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error("items.$i.feature_key")
                                                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="number" step="0.01" min="0.01" wire:model.live.debounce.250ms="items.{{ $i }}.quantity" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 text-right focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                                @error("items.$i.quantity")
                                                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex">
                                                    <span class="inline-flex items-center px-3 py-2 rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 text-sm text-slate-500">$</span>
                                                    <input type="number" step="0.01" min="0" wire:model.live.debounce.250ms="items.{{ $i }}.unit_price" class="flex-1 min-w-0 rounded-r-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 text-right focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                                </div>
                                                @error("items.$i.unit_price")
                                                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="text-sm font-semibold text-slate-900">${{ number_format((float)($row['total'] ?? 0), 2) }}</span>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                @if($editable)
                                                    <button type="button" wire:click="removeItem({{ $i }})" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Remove line">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tax, Discount & Summary -->
                    <div class="border-t border-slate-200 pt-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tax Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" wire:model.live="tax_rate" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                @error('tax_rate')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Discount</label>
                                <div class="flex rounded-xl border border-slate-300 overflow-hidden focus-within:border-slate-900 focus-within:ring-1 focus-within:ring-slate-900 {{ !$editable ? 'bg-slate-50' : '' }}">
                                    <input type="number" step="0.01" min="0" wire:model.live="discount" placeholder="0" class="flex-1 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none border-none disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)>
                                    <select wire:model.live="discount_type" class="border-l border-slate-300 bg-slate-50 px-2 py-2.5 text-sm text-slate-700 font-medium focus:outline-none cursor-pointer hover:bg-slate-100 transition-colors disabled:cursor-not-allowed" @disabled(!$editable)>
                                        <option value="fixed">$</option>
                                        <option value="percentage">%</option>
                                    </select>
                                </div>
                                @error('discount')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
                                    <div class="grid grid-cols-3 gap-4 text-center">
                                        <div>
                                            <p class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-1">Total</p>
                                            <p class="text-lg font-bold text-blue-900">${{ number_format((float)$invoice->amount, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-1">Paid</p>
                                            <p class="text-lg font-bold text-emerald-600">${{ number_format((float)$invoice->total_paid, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-1">Balance</p>
                                            <p class="text-lg font-bold {{ $invoice->balance_due > 0 ? 'text-rose-600' : 'text-slate-900' }}">${{ number_format((float)$invoice->balance_due, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Terms -->
                    <div class="border-t border-slate-200 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Notes</label>
                            <textarea wire:model.live.debounce.350ms="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Terms</label>
                            <textarea wire:model.live.debounce.350ms="terms" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y disabled:bg-slate-50 disabled:text-slate-500" @disabled(!$editable)></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="border-t border-slate-200 pt-5 flex flex-wrap gap-3">
                        <button type="button" wire:click="save" wire:loading.attr="disabled" @disabled(!$editable) class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="save">Save Changes</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                        <button type="button" wire:click="openPaymentModal" wire:loading.attr="disabled" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="xl:col-span-1 space-y-6">
            <!-- Payments Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Payments</h2>
                </div>
                <div class="p-4">
                    @forelse($invoice->payments as $p)
                        <div class="p-3 rounded-xl border border-slate-200 mb-2 last:mb-0">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-base font-semibold text-slate-900">${{ number_format((float)$p->amount, 2) }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ match($p->status) {
                                    'completed', 'succeeded' => 'bg-emerald-100 text-emerald-800',
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'failed' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100 text-slate-800'
                                } }}">
                                    {{ $p->status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">{{ $p->payment_method }} · {{ $p->transaction_id ?? '—' }}</p>
                            <p class="text-xs text-slate-500">{{ $p->processed_at?->format('M j, Y H:i') ?? '—' }}</p>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <p class="text-sm text-slate-500">No payments recorded</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- AI Assistant & Pricing Optimizer -->
            <livewire:admin.invoices.invoice-a-i-assistant :invoiceId="$invoice->id" :editable="$editable" />
            <livewire:admin.invoices.pricing-optimizer :invoiceId="$invoice->id" :editable="$editable" />
        </div>
    </div>

    <!-- Payment Modal -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" wire:click="$set('showPaymentModal', false)"></div>

                <!-- Modal Panel -->
                <div class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900" id="modal-title">Record Manual Payment</h3>
                        <button type="button" wire:click="$set('showPaymentModal', false)" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Amount</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 py-2.5 rounded-l-xl border border-r-0 border-slate-300 bg-slate-50 text-sm text-slate-500">$</span>
                                    <input type="number" step="0.01" wire:model.live="payAmount" class="flex-1 rounded-r-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                </div>
                                @error('payAmount')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Method</label>
                                <select wire:model.live="payMethod" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                    <option value="check">Check</option>
                                    <option value="wire">Wire</option>
                                    <option value="cash">Cash</option>
                                    <option value="stripe">Stripe</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Processed At</label>
                                <input type="datetime-local" wire:model.live="payProcessedAt" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Transaction ID</label>
                                <input type="text" wire:model.live="payTransactionId" placeholder="Optional" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                        </div>
                        <div class="pt-2">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="paySendReceipt" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                <span class="text-sm text-slate-700">Send receipt email to client</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showPaymentModal', false)" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>
                        <button type="button" wire:click="savePayment" wire:loading.attr="disabled" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                            <span wire:loading.remove wire:target="savePayment">Save Payment</span>
                            <span wire:loading wire:target="savePayment">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

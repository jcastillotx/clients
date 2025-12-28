<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Clients</p>
            <h1 class="text-2xl font-semibold text-slate-900">Edit Client</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $client->company_name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.clients.show', $client) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                Back
            </a>
            <button type="button" wire:click="sendPasswordReset" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100 transition-colors">
                Send password reset
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 mb-6">
        <nav class="flex gap-6">
            <button type="button" 
                    wire:click="$set('tab','overview')"
                    class="relative pb-3 text-sm font-medium transition-colors {{ $tab === 'overview' ? 'text-slate-900' : 'text-slate-500 hover:text-slate-700' }}">
                Overview
                @if($tab === 'overview')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-900 rounded-full"></span>
                @endif
            </button>
            <button type="button" 
                    wire:click="$set('tab','activity')"
                    class="relative pb-3 text-sm font-medium transition-colors {{ $tab === 'activity' ? 'text-slate-900' : 'text-slate-500 hover:text-slate-700' }}">
                Activity history
                @if($tab === 'activity')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-900 rounded-full"></span>
                @endif
            </button>
        </nav>
    </div>

    @if($tab === 'overview')
        <form wire:submit.prevent="save" class="relative rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <!-- Loading overlay -->
            <div wire:loading.flex wire:target="save" class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur-sm">
                <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
                    <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-slate-700">Saving…</span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Company name <span class="text-rose-500">*</span></label>
                        <input wire:model.live.debounce.300ms="company_name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        @error('company_name')
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
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contact name <span class="text-rose-500">*</span></label>
                            <input wire:model.live.debounce.300ms="contact_name" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @error('contact_name')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email <span class="text-rose-500">*</span></label>
                            <input wire:model.live.debounce.300ms="email" type="email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @error('email')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone</label>
                            <input wire:model.live.debounce.300ms="phone" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @error('phone')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tier</label>
                            <select wire:model.live="tier" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                @foreach($tiers as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tier')
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs font-medium text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                            <select wire:model.live="status" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                @foreach($statuses as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
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

                <!-- Address Section -->
                <div class="border-t border-slate-200 pt-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">Address</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Street</label>
                            <input wire:model.live.debounce.300ms="address" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">City</label>
                                <input wire:model.live.debounce.300ms="city" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">State</label>
                                <input wire:model.live.debounce.300ms="state" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">ZIP</label>
                                <input wire:model.live.debounce.300ms="zip_code" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Section -->
                <div class="border-t border-slate-200 pt-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Stripe Customer ID</label>
                            <input wire:model.live.debounce.300ms="stripe_customer_id" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="border-t border-slate-200 pt-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Internal notes</label>
                        <textarea wire:model.live.debounce.400ms="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                    </div>
                </div>

                <!-- Linked User Info -->
                <div class="border-t border-slate-200 pt-6">
                    <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-blue-900">Linked user account</p>
                                <p class="text-sm text-blue-700 mt-0.5">{{ $primaryUser?->email ?? 'No user linked yet' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save changes</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </form>
    @endif

    @if($tab === 'activity')
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">When</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Log</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($activities as $a)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">{{ $a->created_at?->diffForHumans() }}</td>
                                <td class="px-6 py-4 text-sm text-slate-900 whitespace-nowrap">{{ $a->user?->name ?? 'System' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                        {{ $a->log_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $a->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm">No activity yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($activities->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    @endif
</div>

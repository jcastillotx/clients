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
    <div class="mb-6 border-b border-slate-200">
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
                    wire:click="$set('tab','profile')"
                    class="relative pb-3 text-sm font-medium transition-colors {{ $tab === 'profile' ? 'text-slate-900' : 'text-slate-500 hover:text-slate-700' }}">
                Business Profile
                @if($tab === 'profile')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-900 rounded-full"></span>
                @endif
            </button>
            <button type="button" 
                    wire:click="$set('tab','services')"
                    class="relative pb-3 text-sm font-medium transition-colors {{ $tab === 'services' ? 'text-slate-900' : 'text-slate-500 hover:text-slate-700' }}">
                Services
                @if($tab === 'services')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-900 rounded-full"></span>
                @endif
            </button>
            <button type="button" 
                    wire:click="$set('tab','activity')"
                    class="relative pb-3 text-sm font-medium transition-colors {{ $tab === 'activity' ? 'text-slate-900' : 'text-slate-500 hover:text-slate-700' }}">
                Activity
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
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tier</label>
                            <select wire:model.live="tier" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                @foreach($tiers as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                            <select wire:model.live="status" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                @foreach($statuses as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
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
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">Billing</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Stripe Customer ID</label>
                            <input wire:model.live.debounce.300ms="stripe_customer_id" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors" placeholder="cus_...">
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="border-t border-slate-200 pt-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">Notes</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client Notes</label>
                            <textarea wire:model.live.debounce.400ms="notes" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                        </div>

                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-amber-800 mb-1.5">Internal Notes (Staff/Admin Only)</label>
                                    <textarea wire:model.live.debounce.400ms="internal_notes" rows="3" class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none transition-colors resize-y" placeholder="Private notes not visible to the client..."></textarea>
                                    <p class="mt-1.5 text-xs text-amber-700">These notes are only visible to staff and admins.</p>
                                </div>
                            </div>
                        </div>
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

    @if($tab === 'profile')
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-6 space-y-6">
                <!-- Business Profile -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Business Profile
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Mission Statement</label>
                            <textarea wire:model.live.debounce.400ms="mission" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y" placeholder="What is the company's core purpose?"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Vision Statement</label>
                            <textarea wire:model.live.debounce.400ms="vision" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y" placeholder="What does the company aspire to become?"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Known Competitors</label>
                        <textarea wire:model.live.debounce.400ms="competitors" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y" placeholder="List main competitors, separated by commas"></textarea>
                        <p class="mt-1.5 text-xs text-slate-500">Used for competitive analysis and brand monitoring.</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" wire:click="saveProfile" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveProfile">Save Profile</span>
                            <span wire:loading wire:target="saveProfile">Saving…</span>
                        </button>
                    </div>
                </div>

                <!-- AI Marketing Strategy Section -->
                <div class="border-t border-slate-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" />
                            </svg>
                            AI Marketing Strategy
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-medium text-purple-700">AI Powered</span>
                        </h3>
                        <button type="button" 
                            wire:click="generateMarketingStrategy" 
                            wire:loading.attr="disabled"
                            wire:target="generateMarketingStrategy"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="generateMarketingStrategy">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" />
                                </svg>
                            </span>
                            <span wire:loading wire:target="generateMarketingStrategy">
                                <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                            </span>
                            <span wire:loading.remove wire:target="generateMarketingStrategy">{{ $marketing_strategy ? 'Regenerate' : 'Generate' }} Strategy</span>
                            <span wire:loading wire:target="generateMarketingStrategy">Generating...</span>
                        </button>
                    </div>
                    
                    <p class="text-xs text-slate-500 mb-3">Generate a comprehensive marketing strategy based on the client's profile.</p>
                    
                    @if($client->marketing_strategy_generated_at)
                        <p class="text-xs text-slate-400 mb-3">Last generated: {{ $client->marketing_strategy_generated_at->diffForHumans() }}</p>
                    @endif

                    @error('marketing_strategy')
                        <div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 p-3">
                            <div class="flex items-start gap-2 text-sm text-rose-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        </div>
                    @enderror

                    @if($marketing_strategy)
                        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                            <div class="px-4 py-2.5 bg-gradient-to-r from-purple-50 to-slate-50 border-b border-slate-200 flex items-center justify-between">
                                <span class="text-xs font-medium text-slate-600">Marketing Strategy</span>
                                <button type="button" wire:click="$set('marketing_strategy', '')" class="text-xs text-slate-400 hover:text-rose-500">Clear</button>
                            </div>
                            <div class="p-4 prose prose-sm max-w-none prose-headings:text-slate-900 prose-p:text-slate-600 prose-li:text-slate-600 max-h-[500px] overflow-y-auto">
                                {!! $marketing_strategy !!}
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-8 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <p class="text-sm text-slate-500">Click "Generate Strategy" to create an AI-powered marketing plan</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($tab === 'services')
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="mb-4 rounded-xl bg-blue-50 border border-blue-200 p-4">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800">Tier Features</p>
                            <p class="text-sm text-blue-700 mt-0.5">The client's tier (<span class="font-medium">{{ ucfirst($tier) }}</span>) includes certain features by default. Additional services checked below will be added on top of tier features.</p>
                        </div>
                    </div>
                </div>

                @php
                    $categories = [
                        'core' => 'Core Features',
                        'brand_monitoring' => 'Brand Monitoring',
                        'ai' => 'AI Features',
                        'advanced' => 'Advanced Features',
                        'collaboration' => 'Collaboration',
                        'research' => 'Research & Consultation',
                    ];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($categories as $categoryKey => $categoryLabel)
                        @if(isset($servicesByCategory[$categoryKey]))
                            <div class="rounded-xl border border-slate-200 bg-slate-50 overflow-hidden">
                                <div class="px-4 py-2.5 bg-slate-100 border-b border-slate-200">
                                    <h4 class="text-xs font-semibold text-slate-700">{{ $categoryLabel }}</h4>
                                </div>
                                <div class="p-3 space-y-2 max-h-80 overflow-y-auto">
                                    @foreach($servicesByCategory[$categoryKey] as $serviceKey => $service)
                                        @php
                                            $tierIncludes = in_array($serviceKey, $tierFeatures[$tier] ?? []);
                                            $isSelected = in_array($serviceKey, $selectedServices);
                                        @endphp
                                        <label class="flex items-start gap-2.5 cursor-pointer group">
                                            <input type="checkbox" 
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0 disabled:opacity-60"
                                                wire:model.live="selectedServices" 
                                                value="{{ $serviceKey }}"
                                                @if($tierIncludes) checked disabled @endif>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">{{ $service['name'] }}</span>
                                                    @if($tierIncludes)
                                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700">Tier</span>
                                                    @elseif($isSelected)
                                                        <span class="inline-flex items-center rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-700">Added</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $service['description'] }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors" wire:click="saveServices" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveServices">Save Services</span>
                        <span wire:loading wire:target="saveServices">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
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

    {{-- Set Password Modal --}}
    @if($showPasswordModal ?? false)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" wire:click="$set('showPasswordModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">Set Password</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">New Password</label>
                            <input type="password" wire:model="newPassword" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" autocomplete="new-password">
                            @error('newPassword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Confirm Password</label>
                            <input type="password" wire:model="newPasswordConfirmation" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" autocomplete="new-password">
                        </div>
                        <p class="text-xs text-slate-500">Password must be at least 8 characters.</p>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showPasswordModal', false)" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="setPassword" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="setPassword">Set Password</span>
                            <span wire:loading wire:target="setPassword">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

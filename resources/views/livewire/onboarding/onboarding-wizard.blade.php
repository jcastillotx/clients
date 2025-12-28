<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900">Onboarding</h2>
        <p class="text-sm text-slate-500 mt-1">Complete your profile and brand discovery</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <livewire:onboarding.onboarding-progress />
            
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                    </svg>
                    <h3 class="text-base font-semibold text-slate-900">Welcome</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-slate-500 mb-4">Resources to get you started:</p>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-sm text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                            </svg>
                            Welcome video
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Quick-start checklist
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            {{ config('client-portal.support_email') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                    </svg>
                    <h3 class="text-base font-semibold text-slate-900">Brand Discovery Questionnaire</h3>
                </div>
                <div class="p-6">
                    @if(!$questionnaire)
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm text-slate-500">No questionnaire available.</p>
                        </div>
                    @else
                        @if($questionnaire->status === 'submitted')
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 mb-6">
                                <div class="flex items-start gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-sm text-emerald-800">Thanks — your questionnaire has been submitted.</p>
                                </div>
                            </div>
                        @endif

                        <div class="mb-6">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ match($questionnaire->status) {
                                'submitted' => 'bg-emerald-100 text-emerald-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                default => 'bg-slate-100 text-slate-800'
                            } }}">
                                Status: {{ ucfirst($questionnaire->status) }}
                            </span>
                        </div>

                        <div class="space-y-5">
                            @foreach((array)($questionnaire->questions ?? []) as $q)
                                @php
                                    $key = $q['key'] ?? null;
                                    $type = $q['type'] ?? 'text';
                                    $label = $q['label'] ?? $key;
                                    $required = !empty($q['required']);
                                    $opts = (array)($q['options'] ?? []);
                                @endphp
                                @if($key)
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                            {{ $label }}
                                            @if($required)
                                                <span class="text-rose-500">*</span>
                                            @endif
                                        </label>

                                        @if($type === 'textarea')
                                            <textarea rows="3" wire:model="answers.{{ $key }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                                        @elseif($type === 'select')
                                            <select wire:model="answers.{{ $key }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                                <option value="">Select…</option>
                                                @foreach($opts as $o)
                                                    <option value="{{ $o }}">{{ $o }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($type === 'multiselect')
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($opts as $o)
                                                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 cursor-pointer hover:bg-slate-100 transition-colors">
                                                        <input type="checkbox" wire:model="answers.{{ $key }}" value="{{ $o }}" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                                        {{ $o }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <input type="text" wire:model="answers.{{ $key }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-slate-200">
                            <button type="button" wire:click="saveProgress" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                                Save Progress
                            </button>
                            <button type="button" wire:click="submitQuestionnaire" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                Submit Questionnaire
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

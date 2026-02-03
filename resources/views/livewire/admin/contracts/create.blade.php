<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-2xl font-semibold text-slate-900">Create Contract</h1>
        </div>
        <a href="{{ route('admin.contracts.index') }}"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
            Back
        </a>
    </div>

    {{-- Flash Messages & Validation Errors --}}
    @include('partials.flash-messages')

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Main Form Column -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Client & Basic Info -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Contract Details</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client <span
                                    class="text-rose-500">*</span></label>
                            <select wire:model.live="client_id"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                                <option value="">Select a client…</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status <span
                                    class="text-rose-500">*</span></label>
                            <select wire:model.live="status"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Title <span
                                class="text-rose-500">*</span></label>
                        <input type="text" wire:model.live="title" placeholder="Contract title..."
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                        <textarea wire:model.live="description" rows="3" placeholder="Contract description or scope..."
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none"></textarea>
                        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contract Value <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-sm text-slate-500">$</span>
                                <input type="number" wire:model.live="value" step="0.01" min="0"
                                    class="w-full rounded-xl border border-slate-300 pl-7 pr-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                            </div>
                            @error('value') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Start Date <span
                                    class="text-rose-500">*</span></label>
                            <input type="date" wire:model.live="start_date"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                            @error('start_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">End Date</label>
                            <input type="date" wire:model.live="end_date"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                            @error('end_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Items -->
            @if($client_id)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-base font-semibold text-slate-900">Linked Items (Optional)</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Project</label>
                            <select wire:model.live="project_id"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                                <option value="">None</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Service Request</label>
                            <select wire:model.live="request_id"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                                <option value="">None</option>
                                @foreach($requests as $r)
                                    <option value="{{ $r->id }}">{{ $r->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            <!-- File Upload -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Contract Document</h2>
                </div>
                <div class="p-6">
                    @if($useAiGenerated && $aiHtml)
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-semibold text-emerald-800">AI-generated content ready</span>
                                </div>
                                <button wire:click="clearAiDraft"
                                    class="text-sm text-emerald-600 hover:underline">Clear</button>
                            </div>
                            <p class="text-xs text-emerald-700 mt-1">A PDF will be generated from the AI content when you
                                save.</p>
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Upload Contract File (PDF, DOC,
                                DOCX)</label>
                            <input type="file" wire:model="contractFile" accept=".pdf,.doc,.docx"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                            @error('contractFile') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <p class="text-xs text-slate-500 mb-2">Or generate a contract using AI:</p>
                        <button wire:click="toggleAiPanel"
                            class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path d="M13 7H7v6h6V7z" />
                                <path fill-rule="evenodd"
                                    d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ $showAiPanel ? 'Hide AI Panel' : 'Generate with AI' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- AI Generation Panel -->
            @if($showAiPanel)
                <div class="rounded-2xl border-2 border-blue-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-blue-200 bg-blue-50 flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path d="M13 7H7v6h6V7z" />
                            <path fill-rule="evenodd"
                                d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z"
                                clip-rule="evenodd" />
                        </svg>
                        <h2 class="text-base font-semibold text-blue-900">AI Contract Generator</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        @if(!$client_id)
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                <p class="text-sm text-amber-800">Please select a client first to use AI generation.</p>
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contract Template <span
                                        class="text-rose-500">*</span></label>
                                <select wire:model.live="templateId"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                                    <option value="">Select a template…</option>
                                    @foreach($templates as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->category }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex gap-3">
                                <button wire:click="generateWithAi" wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                                    <svg wire:loading wire:target="generateWithAi" class="animate-spin h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span wire:loading.remove wire:target="generateWithAi">Generate Draft</span>
                                    <span wire:loading wire:target="generateWithAi">Generating...</span>
                                </button>
                            </div>

                            @if($aiHtml)
                                <div class="mt-4">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Generated Content
                                        Preview</label>
                                    <div
                                        class="border border-slate-200 rounded-xl p-4 bg-slate-50 max-h-64 overflow-y-auto prose prose-sm max-w-none">
                                        {!! \App\Helpers\HtmlSanitizer::sanitizeAI($aiHtml) !!}
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-1 space-y-6">
            <!-- Actions Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden sticky top-6">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Actions</h2>
                </div>
                <div class="p-6 space-y-3">
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center justify-center gap-2">
                        <svg wire:loading wire:target="save" class="animate-spin h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>Create Contract</span>
                    </button>
                </div>
            </div>

            <!-- Help Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Tips</h2>
                </div>
                <div class="p-6 space-y-3 text-sm text-slate-600">
                    <div class="flex gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 flex-shrink-0"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>You can upload an existing contract file or generate one using AI.</span>
                    </div>
                    <div class="flex gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 flex-shrink-0"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Link contracts to projects or requests for better organization.</span>
                    </div>
                    <div class="flex gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500 flex-shrink-0"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Draft contracts can be edited. Active contracts are locked.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin / Contract</p>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $contract->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                <span class="font-mono">{{ $contract->contract_number }}</span>
                · {{ $contract->client?->company_name }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if($contract->file_path)
                <a href="{{ route('contracts.download', $contract) }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Download
                </a>
            @endif
            <a href="{{ route('admin.contracts.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                Back to List
            </a>
        </div>
    </div>

    @if(!$editable)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-sm font-semibold text-amber-800">This contract is locked and cannot be edited.</span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Main Form Column -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Contract Details -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Contract Details</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client</label>
                            <div
                                class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                                {{ $contract->client?->company_name ?? 'Unknown' }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status <span
                                    class="text-rose-500">*</span></label>
                            <select wire:model.live="status" {{ !$editable ? 'disabled' : '' }}
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
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
                        <input type="text" wire:model.live="title" {{ !$editable ? 'disabled' : '' }}
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
                        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                        <textarea wire:model.live="description" rows="3" {{ !$editable ? 'disabled' : '' }}
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed"></textarea>
                        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contract Value <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-sm text-slate-500">$</span>
                                <input type="number" wire:model.live="value" step="0.01" min="0" {{ !$editable ? 'disabled' : '' }}
                                    class="w-full rounded-xl border border-slate-300 pl-7 pr-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
                            </div>
                            @error('value') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Start Date <span
                                    class="text-rose-500">*</span></label>
                            <input type="date" wire:model.live="start_date" {{ !$editable ? 'disabled' : '' }}
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
                            @error('start_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">End Date</label>
                            <input type="date" wire:model.live="end_date" {{ !$editable ? 'disabled' : '' }}
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
                            @error('end_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Items -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Linked Items</h2>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Project</label>
                        <select wire:model.live="project_id" {{ !$editable ? 'disabled' : '' }}
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <option value="">None</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Service Request</label>
                        <select wire:model.live="request_id" {{ !$editable ? 'disabled' : '' }}
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
                            <option value="">None</option>
                            @foreach($requests as $r)
                                <option value="{{ $r->id }}">{{ $r->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contract Document -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Contract Document</h2>
                    @if($contract->file_path)
                        <a href="{{ route('contracts.preview', $contract) }}" target="_blank"
                            class="text-sm font-semibold text-blue-600 hover:underline">
                            Preview PDF
                        </a>
                    @endif
                </div>
                <div class="p-6">
                    @if($contract->file_path)
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-4">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-rose-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ basename($contract->file_path) }}</p>
                                    <p class="text-xs text-slate-500">Current contract document</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($editable)
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Replace Document (PDF, DOC,
                                DOCX)</label>
                            <input type="file" wire:model="contractFile" accept=".pdf,.doc,.docx"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                            @error('contractFile') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <button wire:click="toggleAiPanel"
                                class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path d="M13 7H7v6h6V7z" />
                                    <path fill-rule="evenodd"
                                        d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $showAiPanel ? 'Hide AI Panel' : 'Regenerate with AI' }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- AI Generation Panel -->
            @if($showAiPanel && $editable)
                <div class="rounded-2xl border-2 border-blue-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-blue-200 bg-blue-50 flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path d="M13 7H7v6h6V7z" />
                            <path fill-rule="evenodd"
                                d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z"
                                clip-rule="evenodd" />
                        </svg>
                        <h2 class="text-base font-semibold text-blue-900">AI Contract Regeneration</h2>
                    </div>
                    <div class="p-6 space-y-4">
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
                                <div class="mt-3 flex gap-3">
                                    <button wire:click="applyAiPdf"
                                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Apply as PDF
                                    </button>
                                    <button wire:click="clearAiDraft"
                                        class="text-sm text-slate-600 hover:underline">Clear</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Signature Info -->
            @if($contract->signed_at)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-emerald-200 bg-emerald-100">
                        <h2 class="text-base font-semibold text-emerald-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Signature Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <dt class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">Signed By</dt>
                                <dd class="mt-1 text-sm text-emerald-900">{{ $contract->signed_by ?? 'Unknown' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">Signed At</dt>
                                <dd class="mt-1 text-sm text-emerald-900">
                                    {{ $contract->signed_at->format('M d, Y \a\t g:i A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">Signature IP</dt>
                                <dd class="mt-1 text-sm text-emerald-900 font-mono">{{ $contract->signature_ip ?? 'N/A' }}
                                </dd>
                            </div>
                        </dl>
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
                    @if($editable)
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
                            <span>Save Changes</span>
                        </button>
                    @endif

                    @if($contract->status === 'draft')
                        <button wire:click="sendForSignature" wire:loading.attr="disabled"
                            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                            Send for Signature
                        </button>
                    @endif

                    @if(in_array($contract->status, ['draft', 'pending_signature']))
                        <button wire:click="activateContract" wire:loading.attr="disabled"
                            class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Activate Contract
                        </button>
                    @endif

                    @if(!in_array($contract->status, ['terminated']))
                        <button wire:click="terminateContract"
                            wire:confirm="Are you sure you want to terminate this contract?"
                            class="w-full rounded-xl border border-rose-300 bg-white px-4 py-3 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Terminate Contract
                        </button>
                    @endif

                    <button wire:click="deleteContract"
                        wire:confirm="Are you sure you want to delete this contract? This cannot be undone."
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        Delete Contract
                    </button>
                </div>
            </div>

            <!-- Status Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Status</h2>
                </div>
                <div class="p-6">
                    @php
                        $statusBadge = match ($contract->status) {
                            'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'expired' => 'bg-slate-100 text-slate-700 border-slate-200',
                            'draft' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'pending_signature' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'terminated' => 'bg-rose-100 text-rose-800 border-rose-200',
                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                        };
                    @endphp
                    <div
                        class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold border {{ $statusBadge }}">
                        {{ $contract->status_label }}
                    </div>

                    @if($contract->daysUntilExpiration !== null)
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            @if($contract->daysUntilExpiration > 0)
                                <p class="text-sm text-slate-600">
                                    <span class="font-semibold">{{ $contract->daysUntilExpiration }}</span> days until
                                    expiration
                                </p>
                            @elseif($contract->daysUntilExpiration === 0)
                                <p class="text-sm text-amber-600 font-semibold">Expires today</p>
                            @else
                                <p class="text-sm text-rose-600 font-semibold">Expired {{ abs($contract->daysUntilExpiration) }}
                                    days ago</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Meta Info -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Information</h2>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Contract Number</span>
                        <span class="font-mono text-slate-900">{{ $contract->contract_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Created</span>
                        <span class="text-slate-900">{{ $contract->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Last Updated</span>
                        <span class="text-slate-900">{{ $contract->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
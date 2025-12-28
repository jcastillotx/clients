<div class="rounded-2xl border border-blue-200 bg-white shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-blue-200 bg-blue-50 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                <path d="M13 7H7v6h6V7z" />
                <path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z" clip-rule="evenodd" />
            </svg>
            <h2 class="text-base font-semibold text-blue-900">AI Contract Assistant</h2>
        </div>
        @if($assistantResponse)
            <button wire:click="clearResponse" class="text-sm text-blue-600 hover:underline">Clear</button>
        @endif
    </div>

    <div class="p-6 space-y-4">
        <!-- Mode Selection -->
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-2">Assistant Mode</label>
            <div class="flex flex-wrap gap-2">
                @foreach($modes as $key => $label)
                    <button wire:click="$set('mode', '{{ $key }}')"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $mode === $key ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Template Selection (for generation mode) -->
        @if($mode === 'generate' || $mode === 'clause')
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Template (optional)</label>
                <select wire:model.live="templateId" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none">
                    <option value="">No template</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->category }})</option>
                    @endforeach
                </select>
            </div>
        @endif

        <!-- Prompt Input -->
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Your Request</label>
            <textarea wire:model.live="prompt" rows="3" placeholder="Describe what you need help with..."
                      class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none"></textarea>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-2">
            <button wire:click="askAi" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors disabled:opacity-50">
                <svg wire:loading wire:target="askAi" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="askAi">Ask AI</span>
                <span wire:loading wire:target="askAi">Thinking...</span>
            </button>

            <button wire:click="suggestClauses" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
                Suggest Clauses
            </button>

            <button wire:click="reviewContract" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Review Contract
            </button>
        </div>

        <!-- Response -->
        @if($assistantResponse)
            <div class="mt-4 pt-4 border-t border-slate-200">
                <label class="block text-xs font-semibold text-slate-600 mb-2">AI Response</label>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 prose prose-sm max-w-none max-h-96 overflow-y-auto">
                    {!! nl2br(e($assistantResponse)) !!}
                </div>
            </div>
        @endif

        <!-- Loading State -->
        @if($isLoading)
            <div class="flex items-center justify-center py-8">
                <div class="flex items-center gap-3 text-blue-600">
                    <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium">AI is thinking...</span>
                </div>
            </div>
        @endif
    </div>
</div>

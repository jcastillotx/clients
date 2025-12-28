<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-2xl font-semibold text-slate-900">AI Assistant</h1>
            <p class="text-sm text-slate-500 mt-1">Multi-turn chat with optional page context + knowledge base RAG.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.ai.providers') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                AI Settings
            </a>
            <a href="{{ route('admin.ai.usage') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                Usage
            </a>
        </div>
    </div>

    @if($error)
        <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-rose-800">{{ $error }}</p>
            </div>
        </div>
    @endif

    @if(session()->has('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-emerald-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <!-- Messages Area -->
        <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
            @forelse($this->messages as $m)
                <div class="rounded-xl {{ $m['role'] === 'user' ? 'bg-slate-100' : 'bg-blue-50' }} p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider {{ $m['role'] === 'user' ? 'text-slate-600' : 'text-blue-600' }}">
                            {{ $m['role'] }}
                        </span>
                        <span class="text-xs text-slate-500">{{ $m['created_at'] ?? '' }}</span>
                    </div>
                    <div class="text-sm text-slate-900 whitespace-pre-wrap leading-relaxed">{{ $m['content'] }}</div>
                    
                    @if($m['role'] === 'assistant')
                        <div class="flex items-center gap-2 mt-3 pt-3 border-t {{ $m['role'] === 'user' ? 'border-slate-200' : 'border-blue-200' }}">
                            <button type="button" wire:click="feedback({{ $m['id'] }}, 'up')" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Good response">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                                </svg>
                            </button>
                            <button type="button" wire:click="feedback({{ $m['id'] }}, 'down')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Poor response">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M18 9.5a1.5 1.5 0 11-3 0v-6a1.5 1.5 0 013 0v6zM14 9.667v-5.43a2 2 0 00-1.106-1.79l-.05-.025A4 4 0 0011.057 2H5.64a2 2 0 00-1.962 1.608l-1.2 6A2 2 0 004.44 12H8v4a2 2 0 002 2 1 1 0 001-1v-.667a4 4 0 01.8-2.4l1.4-1.866a4 4 0 00.8-2.4z" />
                                </svg>
                            </button>
                            <button type="button" onclick="document.getElementById('edit_{{ $m['id'] }}').classList.toggle('hidden')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors" title="Edit response">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                            <div class="ml-auto flex items-center gap-2 text-xs text-slate-500">
                                @if($m['provider_used'])
                                    <span class="bg-slate-100 px-2 py-0.5 rounded-full">{{ strtoupper($m['provider_used']) }}</span>
                                @endif
                                @if($m['model_used'])
                                    <span>{{ $m['model_used'] }}</span>
                                @endif
                                @if($m['cost'] !== null)
                                    <span>${{ number_format((float)$m['cost'], 4) }}</span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Edit Area -->
                        <div id="edit_{{ $m['id'] }}" class="hidden mt-3 rounded-xl border border-blue-200 bg-white p-4">
                            <p class="text-xs text-slate-500 mb-2">Edits are captured for prompt/training improvements.</p>
                            <textarea rows="3" wire:model.defer="edits.{{ $m['id'] }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
                            <div class="mt-2">
                                <button type="button" wire:click="saveEdit({{ $m['id'] }})" class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                    Save Edit
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <p class="text-sm text-slate-500">No messages yet. Start a conversation below.</p>
                </div>
            @endforelse
        </div>
        
        <!-- Input Area -->
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
            <div class="flex gap-3">
                <input type="text" wire:model.defer="message" wire:keydown.enter="send" placeholder="Ask about clients, revenue, follow-ups, or say 'Create invoice for ...'" class="flex-1 rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                <button type="button" wire:click="send" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
                    <span wire:loading.remove wire:target="send">Send</span>
                    <span wire:loading wire:target="send">Sending...</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" wire:loading.remove wire:target="send">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

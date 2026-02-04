
    <x-slot name="header">Communication Hub</x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-comments mr-1"></i> Conversations</h3>
                </div>
                <div class="p-4">
                    <input class="mb-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" placeholder="Search messages..." wire:model.live.debounce.300ms="search">
                    <button class="mb-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 hover:bg-slate-50" onclick="document.getElementById('newConversation').classList.toggle('hidden')">
                        <i class="fas fa-plus mr-1"></i> New conversation
                    </button>
                    <div class="mb-2 hidden rounded-lg border border-slate-200 p-3" id="newConversation">
                        <div class="mb-2">
                            <label class="mb-1 text-xs font-medium text-slate-600">Title</label>
                            <input class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" wire:model="newConversationTitle" placeholder="e.g. Website updates">
                        </div>
                        <div class="mb-2">
                            <label class="mb-1 text-xs font-medium text-slate-600">Related request (optional)</label>
                            <select class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900" wire:model="newConversationRequestId">
                                <option value="">None</option>
                                @foreach($requests as $r)
                                    <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800" wire:click="createConversation">
                            Create
                        </button>
                    </div>
                    <div class="flex flex-col">
                        @foreach($conversations as $c)
                            <a href="#" class="block border-b border-slate-100 px-3 py-3 transition last:border-b-0 hover:bg-slate-50 {{ $conversationId === $c->id ? 'bg-slate-900 text-white hover:bg-slate-800' : 'text-slate-900' }}"
                               wire:click.prevent="selectConversation({{ $c->id }})">
                                <div class="font-semibold">{{ $c->title ?? 'Conversation #' . $c->id }}</div>
                                <div class="text-sm {{ $conversationId === $c->id ? 'text-slate-300' : 'text-slate-500' }}">
                                    {{ $c->context_type === 'request' && $c->context_id ? 'Request #' . $c->context_id . ' · ' : '' }}
                                    {{ $c->is_closed ? 'closed' : 'open' }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <small class="mt-2 block text-slate-500">Real-time updates when available.</small>
                </div>
            </div>
        </div>

        <div class="lg:col-span-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-inbox mr-1"></i> Chat</h3>
                    <div class="card-tools text-muted">
                        @if($participants->count())
                            With: {{ $participants->whereNull('client_id')->pluck('name')->implode(', ') ?: 'Support' }}
                        @endif
                    </div>
                </div>
                @if(($pinned?->count() ?? 0) > 0)
                    <div class="card-body border-bottom" style="background:#f8f9fa;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="font-weight-bold"><i class="fas fa-thumbtack mr-1"></i> Pinned</div>
                            <div class="text-muted small">{{ $pinned->count() }} message(s)</div>
                        </div>
                        <div class="mt-2">
                            @foreach($pinned as $pm)
                                <div class="small border rounded p-2 mb-2">
                                    <div class="text-muted">
                                        {{ $pm->sender?->name ?? 'System' }} · {{ $pm->created_at?->format('Y-m-d H:i') }}
                                    </div>
                                    <div style="white-space: pre-wrap;">{{ $pm->body ?? '—' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="card-body" style="height: 420px; overflow:auto;" id="chatScroll">
                    @forelse($messages as $m)
                        @php
                            $mine = $m->sender_id === auth()->id();
                            $readCount = $m->reads?->whereNotNull('read_at')->count() ?? 0;
                        @endphp
                        <div class="mb-3 d-flex {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                            <div style="max-width: 78%;" class="border rounded p-2 {{ $mine ? 'bg-light' : '' }}">
                                <div class="d-flex justify-content-between">
                                    <div class="small font-weight-bold">{{ $m->sender?->name ?? 'System' }}</div>
                                    <div class="small text-muted">{{ $m->created_at?->format('g:i A') }}</div>
                                </div>
                                @if($m->body)
                                    <div style="white-space: pre-wrap;">{{ $m->body }}</div>
                                @endif
                                @foreach($m->attachments as $a)
                                    <div class="mt-2">
                                        <a href="{{ $a->download_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-paperclip mr-1"></i> {{ $a->filename }}
                                        </a>
                                    </div>
                                @endforeach
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="small text-muted">
                                        {{ $mine ? 'Read by ' . $readCount : '' }}
                                    </div>
                                    <div>
                                        <button class="btn btn-xs btn-outline-secondary" wire:click="togglePin({{ $m->id }})">
                                            <i class="fas fa-thumbtack"></i>
                                            {{ $m->is_pinned ? 'Unpin' : 'Pin' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No messages yet.</div>
                    @endforelse
                </div>
                <div class="card-footer">
                    @php
                        $lastIncoming = optional($messages->where('sender_id', '!=', auth()->id())->last())->body ?? '';
                        $ctx = [
                            'conversation_id' => $conversationId,
                            'participants' => $participants->pluck('name')->all(),
                        ];
                    @endphp
                    <livewire:communication.smart-reply-box
                        :clientMessage="$lastIncoming"
                        :contextJson="json_encode($ctx)"
                        :wire:key="'smart-reply-conv-'.$conversationId"
                    />
                    <div class="input-group">
                        <input class="form-control" placeholder="Type a message..." wire:model="message" wire:keydown="typing" wire:keydown.enter.prevent="send">
                        <div class="input-group-append">
                            <label class="btn btn-outline-secondary mb-0" title="Attach file / take photo">
                                <i class="fas fa-camera"></i>
                                <input type="file" wire:model="upload" style="display:none" accept="image/*,application/pdf" capture="environment">
                            </label>
                            <button class="btn btn-primary" wire:click="send">
                                <i class="fas fa-paper-plane mr-1"></i> Send
                            </button>
                        </div>
                    </div>
                    @if(!empty($typingNames))
                        <div class="text-muted small mt-2">{{ implode(', ', $typingNames) }} typing…</div>
                    @endif
                    <small class="text-muted">Mobile: camera capture supported when available.</small>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function scrollChatToBottom() {
                const el = document.getElementById('chatScroll');
                if (el) el.scrollTop = el.scrollHeight;
            }
            document.addEventListener('DOMContentLoaded', scrollChatToBottom);
            document.addEventListener('livewire:navigated', scrollChatToBottom);
            document.addEventListener('livewire:init', () => {
                Livewire.on('message-sent', scrollChatToBottom);
            });

            // If Echo is configured, subscribe to conversation channel
            document.addEventListener('livewire:navigated', () => setupEcho());
            document.addEventListener('DOMContentLoaded', () => setupEcho());

            function setupEcho() {
                if (!window.Echo) return;
                const conversationId = @json($conversationId);
                if (!conversationId) return;
                try {
                    window.Echo.private(`conversation.${conversationId}`)
                        .listen('.message.sent', () => {
                            Livewire.dispatch('message-received');
                            setTimeout(scrollChatToBottom, 150);
                        });
                } catch (e) {
                    // ignore if not configured
                }
            }
        </script>
    @endpush


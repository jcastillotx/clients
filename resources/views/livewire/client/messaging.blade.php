<x-app-layout>
    <x-slot name="header">Communication Hub</x-slot>

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-comments mr-1"></i> Conversations</h3>
                </div>
                <div class="card-body">
                    <input class="form-control mb-2" placeholder="Search messages..." wire:model.live.debounce.300ms="search">
                    <div class="list-group">
                        @foreach($conversations as $c)
                            <a href="#" class="list-group-item list-group-item-action {{ $conversationId === $c->id ? 'active' : '' }}"
                               wire:click.prevent="selectConversation({{ $c->id }})">
                                <div class="font-weight-bold">{{ $c->title ?? 'Conversation #' . $c->id }}</div>
                                <div class="small text-muted">{{ $c->is_closed ? 'closed' : 'open' }}</div>
                            </a>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2">Real-time updates when available.</small>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-inbox mr-1"></i> Chat</h3>
                    <div class="card-tools text-muted">
                        @if($participants->count())
                            With: {{ $participants->whereNull('client_id')->pluck('name')->implode(', ') ?: 'Support' }}
                        @endif
                    </div>
                </div>
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
                                <div class="small text-muted mt-1">
                                    {{ $mine ? 'Read by ' . $readCount : '' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No messages yet.</div>
                    @endforelse
                </div>
                <div class="card-footer">
                    <div class="input-group">
                        <input class="form-control" placeholder="Type a message..." wire:model.defer="message" wire:keydown.enter.prevent="send">
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
            window.addEventListener('message-sent', scrollChatToBottom);

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
</x-app-layout>


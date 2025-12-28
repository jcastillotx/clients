<div>
    <h2 class="mb-3">Messaging (Admin)</h2>

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-comments mr-1"></i> Conversations</h3>
                </div>
                <div class="card-body">
                    <input class="form-control mb-2" placeholder="Search in thread..." wire:model.live.debounce.300ms="search">
                    <div class="list-group" style="max-height: 560px; overflow:auto;">
                        @foreach($conversations as $c)
                            <a href="#" class="list-group-item list-group-item-action {{ $conversationId === $c->id ? 'active' : '' }}"
                               wire:click.prevent="selectConversation({{ $c->id }})">
                                <div class="font-weight-bold">{{ $c->title ?? 'Conversation #' . $c->id }}</div>
                                <div class="small text-muted">
                                    {{ $c->client?->company_name ?? ('Client #' . $c->client_id) }}
                                    @if($c->context_type === 'request' && $c->context_id) · Request #{{ $c->context_id }} @endif
                                </div>
                            </a>
                        @endforeach
                        @if($conversations->isEmpty())
                            <div class="text-muted">No conversations.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-inbox mr-1"></i> Chat</h3>
                    <div class="card-tools text-muted">
                        @if($participants->count())
                            With: {{ $participants->pluck('name')->implode(', ') }}
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
                                    <div class="text-muted">{{ $pm->sender?->name ?? 'System' }} · {{ $pm->created_at?->format('Y-m-d H:i') }}</div>
                                    <div style="white-space: pre-wrap;">{{ $pm->body ?? '—' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card-body" style="height: 420px; overflow:auto;" id="adminChatScroll">
                    @forelse($messages as $m)
                        @php $mine = $m->sender_id === auth()->id(); @endphp
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
                                <div class="text-right mt-2">
                                    <button class="btn btn-xs btn-outline-secondary" wire:click="togglePin({{ $m->id }})">
                                        <i class="fas fa-thumbtack"></i> {{ $m->is_pinned ? 'Unpin' : 'Pin' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">Select a conversation.</div>
                    @endforelse
                </div>

                <div class="card-footer">
                    <div class="input-group">
                        <input class="form-control" placeholder="Type a message..." wire:model="message" wire:keydown="typing" wire:keydown.enter.prevent="send">
                        <div class="input-group-append">
                            <label class="btn btn-outline-secondary mb-0" title="Attach file">
                                <i class="fas fa-paperclip"></i>
                                <input type="file" wire:model="upload" style="display:none">
                            </label>
                            <button class="btn btn-primary" wire:click="send" @if(!$conversationId) disabled @endif>
                                <i class="fas fa-paper-plane mr-1"></i> Send
                            </button>
                        </div>
                    </div>
                    @if(!empty($typingNames))
                        <div class="text-muted small mt-2">{{ implode(', ', $typingNames) }} typing…</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function scrollAdminChatToBottom() {
                const el = document.getElementById('adminChatScroll');
                if (el) el.scrollTop = el.scrollHeight;
            }
            document.addEventListener('DOMContentLoaded', scrollAdminChatToBottom);
            document.addEventListener('livewire:navigated', scrollAdminChatToBottom);
            document.addEventListener('livewire:init', () => {
                Livewire.on('message-sent', scrollAdminChatToBottom);
            });
        </script>
    @endpush
</div>


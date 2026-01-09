<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Messaging</h2>
        <button class="btn btn-primary" wire:click="openNewConversationModal">
            <i class="fas fa-plus mr-1"></i> New Conversation
        </button>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-comments mr-1"></i> Conversations</h3>
                </div>
                <div class="card-body">
                    {{-- Filter tabs --}}
                    <div class="btn-group btn-group-sm btn-block mb-3" role="group">
                        <button type="button" class="btn {{ $conversationFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('conversationFilter', 'all')">All</button>
                        <button type="button" class="btn {{ $conversationFilter === 'clients' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('conversationFilter', 'clients')">Clients</button>
                        <button type="button" class="btn {{ $conversationFilter === 'internal' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('conversationFilter', 'internal')">Internal</button>
                    </div>

                    <input class="form-control mb-2" placeholder="Search in thread..." wire:model.live.debounce.300ms="search">

                    {{-- Quick DM section --}}
                    <div class="dropdown mb-2">
                        <button class="btn btn-sm btn-outline-secondary btn-block dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="fas fa-user mr-1"></i> Quick DM
                        </button>
                        <div class="dropdown-menu" style="max-height: 250px; overflow-y: auto;">
                            @foreach($staffUsers as $staffUser)
                                <a class="dropdown-item" href="#" wire:click.prevent="startDirectMessage({{ $staffUser->id }})">
                                    <i class="fas fa-user-circle mr-1"></i> {{ $staffUser->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="list-group" style="max-height: 460px; overflow:auto;">
                        @foreach($conversations as $c)
                            <a href="#" class="list-group-item list-group-item-action {{ $conversationId === $c->id ? 'active' : '' }}"
                               wire:click.prevent="selectConversation({{ $c->id }})">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="font-weight-bold">
                                        @if($c->context_type === 'direct')
                                            <i class="fas fa-user text-info mr-1"></i>
                                        @elseif($c->context_type === 'internal')
                                            <i class="fas fa-users text-warning mr-1"></i>
                                        @else
                                            <i class="fas fa-building text-success mr-1"></i>
                                        @endif
                                        {{ Str::limit($c->title ?? 'Conversation #' . $c->id, 25) }}
                                    </div>
                                </div>
                                <div class="small {{ $conversationId === $c->id ? 'text-light' : 'text-muted' }}">
                                    @if($c->client)
                                        {{ $c->client->company_name }}
                                    @elseif($c->context_type === 'internal')
                                        Internal Team
                                    @elseif($c->context_type === 'direct')
                                        Direct Message
                                    @endif
                                    @if($c->last_message_at)
                                        · {{ $c->last_message_at->diffForHumans() }}
                                    @endif
                                </div>
                            </a>
                        @endforeach
                        @if($conversations->isEmpty())
                            <div class="text-muted text-center py-3">
                                <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">No conversations.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-inbox mr-1"></i>
                        @if($currentConversation)
                            {{ $currentConversation->title ?? 'Chat' }}
                        @else
                            Chat
                        @endif
                    </h3>
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
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
                            <p>Select a conversation or start a new one.</p>
                        </div>
                    @endforelse
                </div>

                <div class="card-footer">
                    <div class="input-group">
                        <input class="form-control" placeholder="Type a message... (use @name to mention)" wire:model="message" wire:keydown="typing" wire:keydown.enter.prevent="send">
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

    {{-- New Conversation Modal --}}
    @if($showNewConversationModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>New Conversation</h5>
                    <button type="button" class="close" wire:click="closeNewConversationModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Conversation Type</label>
                        <div class="btn-group btn-block" role="group">
                            <button type="button" class="btn {{ $newConversationType === 'internal' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('newConversationType', 'internal')">
                                <i class="fas fa-users mr-1"></i> Internal (Staff/Admin)
                            </button>
                            <button type="button" class="btn {{ $newConversationType === 'client' ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="$set('newConversationType', 'client')">
                                <i class="fas fa-building mr-1"></i> Client Conversation
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newConversationTitle">Conversation Title</label>
                        <input type="text" class="form-control" id="newConversationTitle" wire:model="newConversationTitle" placeholder="Enter a title for this conversation">
                        @error('title') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    @if($newConversationType === 'client')
                    <div class="form-group">
                        <label for="selectedClientId">Select Client</label>
                        <select class="form-control" id="selectedClientId" wire:model="selectedClientId">
                            <option value="">-- Select a client --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                        @error('clientId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <div class="form-group">
                        <label>{{ $newConversationType === 'internal' ? 'Select Participants' : 'Add Additional Staff (Optional)' }}</label>
                        <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                            @foreach($staffUsers as $staffUser)
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="participant_{{ $staffUser->id }}" value="{{ $staffUser->id }}" wire:model="selectedParticipants">
                                    <label class="custom-control-label" for="participant_{{ $staffUser->id }}">
                                        {{ $staffUser->name }} <span class="text-muted small">({{ $staffUser->email }})</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('participants') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeNewConversationModal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="createConversation">
                        <i class="fas fa-check mr-1"></i> Create Conversation
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

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

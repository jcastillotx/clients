<x-admin-tailwind-layout>
    <x-slot name="title">Messaging</x-slot>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900">Messaging</h2>
            <button wire:click="openNewConversationModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus"></i>
                <span>New Conversation</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Conversations List -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg border border-slate-200 shadow-sm">
                    <div class="p-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-comments text-slate-600"></i>
                            Conversations
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        <!-- Filter Tabs -->
                        <div class="flex gap-1 bg-slate-100 p-1 rounded-lg">
                            <button wire:click="$set('conversationFilter', 'all')"
                                class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $conversationFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                                All
                            </button>
                            <button wire:click="$set('conversationFilter', 'clients')"
                                class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $conversationFilter === 'clients' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                                Clients
                            </button>
                            <button wire:click="$set('conversationFilter', 'internal')"
                                class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $conversationFilter === 'internal' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                                Internal
                            </button>
                        </div>

                        <!-- Search -->
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search in thread..."
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                        <!-- Quick DM -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-user text-slate-500"></i>
                                    Quick DM
                                </span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @foreach($staffUsers as $staffUser)
                                    <button wire:click="startDirectMessage({{ $staffUser->id }})"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 text-left">
                                        <i class="fas fa-user-circle text-slate-400"></i>
                                        {{ $staffUser->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Conversations -->
                        <div class="space-y-1 max-h-96 overflow-y-auto">
                            @foreach($conversations as $c)
                                <button wire:click="selectConversation({{ $c->id }})"
                                    class="w-full text-left px-3 py-2.5 rounded-lg transition-colors {{ $conversationId === $c->id ? 'bg-blue-50 border border-blue-200' : 'hover:bg-slate-50' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="flex items-center gap-2 text-sm font-semibold {{ $conversationId === $c->id ? 'text-blue-900' : 'text-slate-900' }}">
                                                @if($c->context_type === 'direct')
                                                    <i class="fas fa-user text-blue-500 text-xs"></i>
                                                @elseif($c->context_type === 'internal')
                                                    <i class="fas fa-users text-amber-500 text-xs"></i>
                                                @else
                                                    <i class="fas fa-building text-green-500 text-xs"></i>
                                                @endif
                                                <span
                                                    class="truncate">{{ Str::limit($c->title ?? 'Conversation #' . $c->id, 25) }}</span>
                                            </div>
                                            <div
                                                class="text-xs {{ $conversationId === $c->id ? 'text-blue-700' : 'text-slate-500' }} mt-0.5">
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
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                            @if($conversations->isEmpty())
                                <div class="text-center py-8 text-slate-500">
                                    <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                                    <p class="text-sm">No conversations.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col h-[600px]">
                    <!-- Chat Header -->
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-inbox text-slate-600"></i>
                            @if($currentConversation)
                                {{ $currentConversation->title ?? 'Chat' }}
                            @else
                                Chat
                            @endif
                        </h3>
                        @if($participants->count())
                            <p class="text-sm text-slate-600 mt-1">
                                With: {{ $participants->pluck('name')->implode(', ') }}
                            </p>
                        @endif
                    </div>

                    <!-- Pinned Messages -->
                    @if(($pinned?->count() ?? 0) > 0)
                        <div class="px-6 py-3 bg-amber-50 border-b border-amber-200">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-semibold text-amber-900 flex items-center gap-2">
                                    <i class="fas fa-thumbtack"></i>
                                    Pinned
                                </div>
                                <span class="text-xs text-amber-700">{{ $pinned->count() }} message(s)</span>
                            </div>
                            <div class="space-y-2">
                                @foreach($pinned as $pm)
                                    <div class="text-sm bg-white border border-amber-200 rounded-lg p-2">
                                        <div class="text-xs text-slate-600">{{ $pm->sender?->name ?? 'System' }} ·
                                            {{ $pm->created_at?->format('Y-m-d H:i') }}</div>
                                        <div class="mt-1 whitespace-pre-wrap">{{ $pm->body ?? '—' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4" id="adminChatScroll">
                        @forelse($messages as $m)
                            @php $mine = $m->sender_id === auth()->id(); @endphp
                            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div
                                    class="max-w-[78%] {{ $mine ? 'bg-blue-50 border-blue-200' : 'bg-slate-50 border-slate-200' }} border rounded-lg p-3">
                                    <div class="flex items-center justify-between gap-4 mb-1">
                                        <span
                                            class="text-sm font-semibold text-slate-900">{{ $m->sender?->name ?? 'System' }}</span>
                                        <span class="text-xs text-slate-500">{{ $m->created_at?->format('g:i A') }}</span>
                                    </div>
                                    @if($m->body)
                                        <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $m->body }}</div>
                                    @endif
                                    @foreach($m->attachments as $a)
                                        <div class="mt-2">
                                            <a href="{{ $a->download_url }}" target="_blank" rel="noopener"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                                                <i class="fas fa-paperclip"></i>
                                                {{ $a->filename }}
                                            </a>
                                        </div>
                                    @endforeach
                                    <div class="flex justify-end mt-2">
                                        <button wire:click="togglePin({{ $m->id }})"
                                            class="text-xs px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded">
                                            <i class="fas fa-thumbtack"></i>
                                            {{ $m->is_pinned ? 'Unpin' : 'Pin' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex items-center justify-center h-full text-center text-slate-500">
                                <div>
                                    <i class="fas fa-comments text-5xl mb-3 opacity-30"></i>
                                    <p>Select a conversation or start a new one.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Message Input -->
                    <div class="px-6 py-4 border-t border-slate-200">
                        <div class="flex gap-2">
                            <input type="text" wire:model="message" wire:keydown="typing"
                                wire:keydown.enter.prevent="send" placeholder="Type a message... (use @name to mention)"
                                class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <label
                                class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer"
                                title="Attach file">
                                <i class="fas fa-paperclip text-slate-600"></i>
                                <input type="file" wire:model="upload" class="hidden">
                            </label>
                            <button wire:click="send" @if(!$conversationId) disabled @endif
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-paper-plane"></i>
                                <span>Send</span>
                            </button>
                        </div>
                        @if(!empty($typingNames))
                            <div class="text-xs text-slate-500 mt-2">{{ implode(', ', $typingNames) }} typing…</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Conversation Modal -->
    @if($showNewConversationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-50 transition-opacity"
                    wire:click="closeNewConversationModal"></div>

                <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-plus-circle text-blue-600"></i>
                            New Conversation
                        </h3>
                        <button wire:click="closeNewConversationModal" class="text-slate-400 hover:text-slate-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 space-y-4">
                        <!-- Conversation Type -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Conversation Type</label>
                            <div class="flex gap-2">
                                <button wire:click="$set('newConversationType', 'internal')"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border {{ $newConversationType === 'internal' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                                    <i class="fas fa-users"></i>
                                    Internal (Staff/Admin)
                                </button>
                                <button wire:click="$set('newConversationType', 'client')"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border {{ $newConversationType === 'client' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                                    <i class="fas fa-building"></i>
                                    Client Conversation
                                </button>
                            </div>
                        </div>

                        <!-- Title -->
                        <div>
                            <label for="newConversationTitle"
                                class="block text-sm font-semibold text-slate-700 mb-1.5">Conversation Title</label>
                            <input type="text" id="newConversationTitle" wire:model="newConversationTitle"
                                placeholder="Enter a title for this conversation"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Client Selection -->
                        @if($newConversationType === 'client')
                            <div>
                                <label for="selectedClientId" class="block text-sm font-semibold text-slate-700 mb-1.5">Select
                                    Client</label>
                                <select id="selectedClientId" wire:model="selectedClientId"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select a client --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                                    @endforeach
                                </select>
                                @error('clientId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <!-- Participants -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                {{ $newConversationType === 'internal' ? 'Select Participants' : 'Add Additional Staff (Optional)' }}
                            </label>
                            <div class="border border-slate-300 rounded-lg p-3 max-h-48 overflow-y-auto space-y-2">
                                @foreach($staffUsers as $staffUser)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" value="{{ $staffUser->id }}" wire:model="selectedParticipants"
                                            class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                        <span class="text-sm text-slate-700">
                                            {{ $staffUser->name }} <span class="text-slate-500">({{ $staffUser->email }})</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('participants') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200">
                        <button wire:click="closeNewConversationModal"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                            Cancel
                        </button>
                        <button wire:click="createConversation"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-check"></i>
                            Create Conversation
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
</x-admin-tailwind-layout>
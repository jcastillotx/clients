<?php

namespace App\Http\Livewire\Communication;

use App\Events\MessageSent;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRead;
use App\Models\User;
use App\Notifications\UserMentionedInMessageNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessagingHub extends Component
{
    use WithFileUploads;

    public $layout = 'layouts.admin-tailwind';

    public ?int $conversationId = null;

    public string $message = '';

    public $upload;

    public string $search = '';

    public string $conversationFilter = 'all'; // all, clients, internal

    public bool $showNewConversationModal = false;

    public string $newConversationType = 'internal'; // internal, client

    public string $newConversationTitle = '';

    public array $selectedParticipants = [];

    public ?int $selectedClientId = null;

    protected $listeners = [
        'message-received' => '$refresh',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
    }

    public function openNewConversationModal(): void
    {
        $this->showNewConversationModal = true;
        $this->newConversationType = 'internal';
        $this->newConversationTitle = '';
        $this->selectedParticipants = [];
        $this->selectedClientId = null;
    }

    public function closeNewConversationModal(): void
    {
        $this->showNewConversationModal = false;
    }

    public function createConversation(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make([
            'title' => $this->newConversationTitle,
            'type' => $this->newConversationType,
            'participants' => $this->selectedParticipants,
            'clientId' => $this->selectedClientId,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:internal,client'],
            'participants' => ['required_if:type,internal', 'array'],
            'clientId' => ['required_if:type,client', 'nullable', 'exists:clients,id'],
        ])->validate();

        if ($this->newConversationType === 'internal') {
            // Internal conversation (admin/staff to admin/staff)
            $conv = Conversation::create([
                'client_id' => null,
                'context_type' => 'internal',
                'title' => trim($this->newConversationTitle),
                'is_closed' => false,
            ]);

            // Add creator as participant
            $conv->participants()->attach($user->id, ['role' => 'staff']);

            // Add selected participants
            foreach ($this->selectedParticipants as $participantId) {
                $conv->participants()->syncWithoutDetaching([(int)$participantId => ['role' => 'staff']]);
            }
        } else {
            // Client conversation
            $conv = Conversation::create([
                'client_id' => $this->selectedClientId,
                'context_type' => 'general',
                'title' => trim($this->newConversationTitle),
                'is_closed' => false,
            ]);

            // Add creator as participant
            $conv->participants()->attach($user->id, ['role' => 'staff']);

            // Add client users as participants
            $clientUsers = User::where('client_id', $this->selectedClientId)->pluck('id');
            foreach ($clientUsers as $clientUserId) {
                $conv->participants()->syncWithoutDetaching([$clientUserId => ['role' => 'client']]);
            }

            // Add selected staff participants
            foreach ($this->selectedParticipants as $participantId) {
                $conv->participants()->syncWithoutDetaching([(int)$participantId => ['role' => 'staff']]);
            }
        }

        $this->conversationId = $conv->id;
        $this->showNewConversationModal = false;
        $this->newConversationType = 'internal';
        $this->newConversationTitle = '';
        $this->selectedParticipants = [];
        $this->selectedClientId = null;

        session()->flash('success', 'Conversation created successfully.');
    }

    public function startDirectMessage(int $userId): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $otherUser = User::findOrFail($userId);

        // Check if a DM conversation already exists between these two users
        $existingConv = Conversation::whereNull('client_id')
            ->where('context_type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('users.id', $user->id))
            ->whereHas('participants', fn($q) => $q->where('users.id', $otherUser->id))
            ->first();

        if ($existingConv) {
            $this->conversationId = $existingConv->id;
            return;
        }

        // Create new DM conversation
        $conv = Conversation::create([
            'client_id' => null,
            'context_type' => 'direct',
            'title' => "DM: {$user->name} & {$otherUser->name}",
            'is_closed' => false,
        ]);

        $conv->participants()->attach($user->id, ['role' => 'staff']);
        $conv->participants()->attach($otherUser->id, ['role' => 'staff']);

        $this->conversationId = $conv->id;
    }

    public function selectConversation(int $id): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        $this->conversationId = $id;
        $this->markVisibleAsRead();
    }

    public function typing(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        if (! $this->conversationId) {
            return;
        }
        Cache::put("conv:{$this->conversationId}:typing:{$user->id}", now()->toISOString(), now()->addSeconds(10));
    }

    public function send(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        abort_unless($this->conversationId, 422);

        $conv = Conversation::query()->with('participants')->findOrFail($this->conversationId);

        Validator::make([
            'message' => $this->message,
            'upload' => $this->upload,
        ], [
            'message' => ['nullable', 'string', 'max:5000'],
            'upload' => ['nullable', 'file', 'max:51200'],
        ])->validate();

        abort_unless(trim($this->message) !== '' || $this->upload, 422, 'Message or file required.');

        // Ensure staff is a participant (so reads work)
        $conv->participants()->syncWithoutDetaching([$user->id => ['role' => 'staff']]);

        // Resolve mentions
        $mentions = $this->resolveMentions($conv);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user->id,
            'body' => trim($this->message) !== '' ? $this->message : null,
            'type' => $this->upload ? 'file' : 'text',
            'mentions' => $mentions ?: null,
        ]);

        if ($this->upload) {
            $path = $this->upload->store('chat', 'attachments');
            MessageAttachment::create([
                'message_id' => $msg->id,
                'disk' => 'attachments',
                'path' => $path,
                'filename' => $this->upload->getClientOriginalName(),
                'mime_type' => $this->upload->getClientMimeType(),
                'size_bytes' => (int) $this->upload->getSize(),
            ]);
        }

        MessageRead::updateOrCreate([
            'message_id' => $msg->id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        $conv->update(['last_message_at' => now()]);
        $this->reset(['message', 'upload']);

        // Notify mentioned users
        if (! empty($mentions)) {
            $targets = User::query()->whereIn('id', $mentions)->get();
            if ($targets->isNotEmpty()) {
                Notification::send($targets, new UserMentionedInMessageNotification($msg));
            }
        }

        try {
            broadcast(new MessageSent($msg))->toOthers();
        } catch (\Throwable) {
            // ignore
        }

        $this->dispatch('message-sent', conversationId: $conv->id);
    }

    protected function resolveMentions(Conversation $conv): array
    {
        $body = (string) $this->message;
        if ($body === '') {
            return [];
        }

        preg_match_all('/@([A-Za-z0-9_\\.\\-]+)/', $body, $m);
        $tokens = array_unique(array_map('strtolower', $m[1] ?? []));
        if (empty($tokens)) {
            return [];
        }

        $participants = $conv->participants()->get(['users.id', 'users.name']);
        $byName = [];
        foreach ($participants as $p) {
            $byName[strtolower(str_replace(' ', '', (string) $p->name))] = (int) $p->id;
        }

        $ids = [];
        foreach ($tokens as $t) {
            if ($t === 'all') {
                foreach ($participants as $p) {
                    $ids[] = (int) $p->id;
                }
                continue;
            }
            $key = strtolower(str_replace(' ', '', $t));
            if (isset($byName[$key])) {
                $ids[] = (int) $byName[$key];
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function togglePin(int $messageId): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        abort_unless($this->conversationId, 422);

        $msg = Message::query()->where('conversation_id', $this->conversationId)->findOrFail($messageId);
        $pin = ! $msg->is_pinned;
        $msg->update([
            'is_pinned' => $pin,
            'pinned_at' => $pin ? now() : null,
            'pinned_by' => $pin ? $user->id : null,
        ]);
    }

    protected function markVisibleAsRead(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->conversationId) {
            return;
        }

        $messages = Message::query()
            ->where('conversation_id', $this->conversationId)
            ->where('sender_id', '!=', $user->id)
            ->latest('id')
            ->limit(100)
            ->get(['id']);

        foreach ($messages as $m) {
            MessageRead::updateOrCreate([
                'message_id' => $m->id,
                'user_id' => $user->id,
            ], [
                'read_at' => now(),
            ]);
        }
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        // Get conversations based on filter
        $conversationsQuery = Conversation::query()
            ->with('client')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($this->conversationFilter === 'clients') {
            $conversationsQuery->whereNotNull('client_id');
        } elseif ($this->conversationFilter === 'internal') {
            $conversationsQuery->whereNull('client_id');
        }

        $conversations = $conversationsQuery->limit(200)->get();

        $messages = collect();
        $participants = collect();
        $pinned = collect();
        $typingNames = [];
        $currentConversation = null;

        if ($this->conversationId) {
            $currentConversation = Conversation::query()->with('participants', 'client')->find($this->conversationId);
            if ($currentConversation) {
                $participants = $currentConversation->participants;

                $pinned = Message::query()
                    ->where('conversation_id', $currentConversation->id)
                    ->where('is_pinned', true)
                    ->with(['sender'])
                    ->orderByDesc('pinned_at')
                    ->limit(10)
                    ->get();

                $messages = Message::query()
                    ->where('conversation_id', $currentConversation->id)
                    ->when($this->search, fn ($q) => $q->where('body', 'like', '%'.$this->search.'%'))
                    ->with(['sender', 'attachments', 'reads'])
                    ->orderBy('id', 'asc')
                    ->limit(300)
                    ->get();

                $this->markVisibleAsRead();

                foreach ($participants as $p) {
                    if ((int) $p->id === (int) $user->id) {
                        continue;
                    }
                    if (Cache::has("conv:{$currentConversation->id}:typing:{$p->id}")) {
                        $typingNames[] = (string) $p->name;
                    }
                }
            }
        }

        // Get staff/admin users for new conversation modal
        $staffUsers = User::query()
            ->whereNull('client_id')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get clients for new conversation modal
        $clients = Client::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        return view('livewire.communication.messaging-hub', compact(
            'conversations',
            'messages',
            'participants',
            'pinned',
            'typingNames',
            'staffUsers',
            'clients',
            'currentConversation'
        ));
    }
}

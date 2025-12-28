<?php

namespace App\Http\Livewire\Client;

use App\Events\MessageSent;
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

class Messaging extends Component
{
    use WithFileUploads;

    public ?int $conversationId = null;

    public string $message = '';

    public $upload;

    public string $search = '';

    public ?int $newConversationRequestId = null;

    public string $newConversationTitle = '';

    protected $listeners = [
        'message-received' => '$refresh',
        'smart-reply-selected' => 'applySmartReply',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        // Ensure a default conversation exists for this client
        $conv = Conversation::query()
            ->where('client_id', $user->client_id)
            ->orderByDesc('id')
            ->first();

        if (! $conv) {
            $conv = Conversation::create([
                'client_id' => $user->client_id,
                'title' => 'Support Chat',
                'is_closed' => false,
            ]);
            $conv->participants()->syncWithoutDetaching([$user->id => ['role' => 'client']]);

            // auto-add up to 3 staff members (best effort)
            $staff = User::query()->whereNull('client_id')->limit(3)->pluck('id')->all();
            foreach ($staff as $sid) {
                $conv->participants()->syncWithoutDetaching([$sid => ['role' => 'staff']]);
            }
        }

        $this->conversationId = $conv->id;
    }

    public function selectConversation(int $id): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless(Conversation::query()->where('client_id', $user->client_id)->whereKey($id)->exists(), 403);
        $this->conversationId = $id;
        $this->markVisibleAsRead();
    }

    public function send(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($this->conversationId, 422);

        $conv = Conversation::query()->where('client_id', $user->client_id)->findOrFail($this->conversationId);

        Validator::make([
            'message' => $this->message,
            'upload' => $this->upload,
        ], [
            'message' => ['nullable', 'string', 'max:5000'],
            'upload' => ['nullable', 'file', 'max:51200'],
        ])->validate();

        abort_unless(trim($this->message) !== '' || $this->upload, 422, 'Message or file required.');

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

        // mark sender read
        MessageRead::updateOrCreate([
            'message_id' => $msg->id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        $this->reset(['message', 'upload']);
        $conv->update(['last_message_at' => now()]);

        // Notify mentioned users (best-effort)
        if (! empty($mentions)) {
            $targets = User::query()->whereIn('id', $mentions)->get();
            if ($targets->isNotEmpty()) {
                Notification::send($targets, new UserMentionedInMessageNotification($msg));
            }
        }

        // Best-effort: notify JS to broadcast refresh (Echo listener)
        try {
            broadcast(new MessageSent($msg))->toOthers();
        } catch (\Throwable) {
            // broadcasting might not be configured; ignore
        }

        $this->dispatch('message-sent', conversationId: $conv->id);
    }

    public function togglePin(int $messageId): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($this->conversationId, 422);

        $conv = Conversation::query()->where('client_id', $user->client_id)->findOrFail($this->conversationId);

        $msg = Message::query()
            ->where('conversation_id', $conv->id)
            ->findOrFail($messageId);

        $pin = ! $msg->is_pinned;
        $msg->update([
            'is_pinned' => $pin,
            'pinned_at' => $pin ? now() : null,
            'pinned_by' => $pin ? $user->id : null,
        ]);
    }

    public function createConversation(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        Validator::make([
            'title' => $this->newConversationTitle,
            'requestId' => $this->newConversationRequestId,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'requestId' => ['nullable', 'integer'],
        ])->validate();

        $conv = Conversation::create([
            'client_id' => $user->client_id,
            'context_type' => $this->newConversationRequestId ? 'request' : 'general',
            'context_id' => $this->newConversationRequestId ?: null,
            'title' => trim($this->newConversationTitle),
            'is_closed' => false,
            'last_message_at' => null,
        ]);
        $conv->participants()->syncWithoutDetaching([$user->id => ['role' => 'client']]);

        $staff = User::query()->whereNull('client_id')->limit(3)->pluck('id')->all();
        foreach ($staff as $sid) {
            $conv->participants()->syncWithoutDetaching([$sid => ['role' => 'staff']]);
        }

        $this->conversationId = $conv->id;
        $this->reset(['newConversationTitle', 'newConversationRequestId']);
        session()->flash('success', 'Conversation created.');
    }

    public function typing(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        if (! $this->conversationId) {
            return;
        }

        Cache::put("conv:{$this->conversationId}:typing:{$user->id}", now()->toISOString(), now()->addSeconds(10));
    }

    /**
     * @return array<int,int>
     */
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

        $ids = array_values(array_unique(array_filter($ids)));

        return $ids;
    }

    public function applySmartReply(string $text): void
    {
        $this->message = $text;
    }

    public function markVisibleAsRead(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        if (! $this->conversationId) {
            return;
        }

        $messages = Message::query()
            ->where('conversation_id', $this->conversationId)
            ->where('sender_id', '!=', $user->id)
            ->latest('id')
            ->limit(50)
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
        abort_unless($user && $user->isClient(), 403);

        $conversations = Conversation::query()
            ->where('client_id', $user->client_id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $messages = collect();
        $participants = collect();
        $pinned = collect();
        $typingNames = [];

        if ($this->conversationId) {
            $conv = Conversation::query()
                ->where('client_id', $user->client_id)
                ->with('participants')
                ->find($this->conversationId);

            if ($conv) {
                $participants = $conv->participants;

                $pinned = Message::query()
                    ->where('conversation_id', $conv->id)
                    ->where('is_pinned', true)
                    ->with(['sender'])
                    ->orderByDesc('pinned_at')
                    ->limit(10)
                    ->get();

                $messages = Message::query()
                    ->where('conversation_id', $conv->id)
                    ->when($this->search, function ($q) {
                        $q->where('body', 'like', '%'.$this->search.'%');
                    })
                    ->with(['sender', 'attachments', 'reads'])
                    ->orderBy('id', 'asc')
                    ->limit(200)
                    ->get();

                // auto mark read
                $this->markVisibleAsRead();

                // typing indicator
                foreach ($participants as $p) {
                    if ((int) $p->id === (int) $user->id) {
                        continue;
                    }
                    if (Cache::has("conv:{$conv->id}:typing:{$p->id}")) {
                        $typingNames[] = (string) $p->name;
                    }
                }
            }
        }

        $requests = \App\Models\Request::query()
            ->where('client_id', $user->client_id)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'title']);

        return view('livewire.client.messaging', compact('conversations', 'messages', 'participants', 'requests', 'pinned', 'typingNames'))->layout('layouts.app');
    }
}

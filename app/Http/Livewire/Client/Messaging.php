<?php

namespace App\Http\Livewire\Client;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRead;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
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

        if (!$conv) {
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

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user->id,
            'body' => trim($this->message) !== '' ? $this->message : null,
            'type' => $this->upload ? 'file' : 'text',
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

        // Best-effort: notify JS to broadcast refresh (Echo listener)
        try {
            broadcast(new MessageSent($msg))->toOthers();
        } catch (\Throwable) {
            // broadcasting might not be configured; ignore
        }

        $this->dispatch('message-sent', conversationId: $conv->id);
    }

    public function applySmartReply(string $text): void
    {
        $this->message = $text;
    }

    public function markVisibleAsRead(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        if (!$this->conversationId) {
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
            ->orderByDesc('id')
            ->get();

        $messages = collect();
        $participants = collect();

        if ($this->conversationId) {
            $conv = Conversation::query()
                ->where('client_id', $user->client_id)
                ->with('participants')
                ->find($this->conversationId);

            if ($conv) {
                $participants = $conv->participants;

                $messages = Message::query()
                    ->where('conversation_id', $conv->id)
                    ->when($this->search, function ($q) {
                        $q->where('body', 'like', '%' . $this->search . '%');
                    })
                    ->with(['sender', 'attachments', 'reads'])
                    ->orderBy('id', 'asc')
                    ->limit(200)
                    ->get();

                // auto mark read
                $this->markVisibleAsRead();
            }
        }

        return view('livewire.client.messaging', compact('conversations', 'messages', 'participants'));
    }
}


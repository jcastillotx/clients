<?php

namespace App\Http\Livewire\Communication;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessagingHub extends Component
{
    use WithFileUploads;

    public ?int $conversationId = null;

    public string $message = '';

    public $upload;

    public string $search = '';

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
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

        MessageRead::updateOrCreate([
            'message_id' => $msg->id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        $conv->update(['last_message_at' => now()]);
        $this->reset(['message', 'upload']);

        try {
            broadcast(new MessageSent($msg))->toOthers();
        } catch (\Throwable) {
            // ignore
        }

        $this->dispatch('message-sent', conversationId: $conv->id);
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

        $conversations = Conversation::query()
            ->with('client')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $messages = collect();
        $participants = collect();
        $pinned = collect();
        $typingNames = [];

        if ($this->conversationId) {
            $conv = Conversation::query()->with('participants')->find($this->conversationId);
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
                    if (Cache::has("conv:{$conv->id}:typing:{$p->id}")) {
                        $typingNames[] = (string) $p->name;
                    }
                }
            }
        }

        return view('livewire.communication.messaging-hub', compact('conversations', 'messages', 'participants', 'pinned', 'typingNames'));
    }
}

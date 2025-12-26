<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    $conv = Conversation::query()->find($conversationId);
    if (!$conv) {
        return false;
    }

    // Clients: must match client_id; Staff: must be participant
    if ($user->isClient()) {
        return (int) $user->client_id === (int) $conv->client_id;
    }

    return $conv->participants()->where('users.id', $user->id)->exists();
});


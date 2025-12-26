<?php

namespace App\Http\Livewire\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiMessageFeedback;
use App\Services\AI\AIProviderManager;
use App\Services\AI\KnowledgeBaseRagService;
use App\Services\AI\PromptTemplateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClientAssistantChat extends Component
{
    public ?int $conversationId = null;
    public string $message = '';
    public array $messages = [];
    public ?string $error = null;

    public bool $needsHuman = false;

    public function mount(?int $conversation = null): void
    {
        $this->authorizeClient();

        $this->conversationId = $conversation;
        if ($this->conversationId) {
            $this->loadConversation();
            return;
        }

        $conv = AiConversation::create([
            'client_id' => Auth::user()?->client_id,
            'user_id' => Auth::id(),
            'context_type' => 'general',
            'context_id' => null,
            'title' => 'Client assistant chat',
        ]);
        $this->conversationId = $conv->id;
        $this->loadConversation();
    }

    public function loadConversation(): void
    {
        if (!$this->conversationId) return;

        $rows = AiMessage::query()
            ->where('ai_conversation_id', $this->conversationId)
            ->orderBy('id')
            ->get();

        $this->messages = $rows->map(fn ($m) => [
            'id' => $m->id,
            'role' => $m->role,
            'content' => $m->content,
            'created_at' => $m->created_at?->toDateTimeString(),
        ])->all();
    }

    public function send(AIProviderManager $providers, PromptTemplateService $templates, KnowledgeBaseRagService $rag): void
    {
        $this->authorizeClient();

        $this->error = null;
        $text = trim($this->message);
        if ($text === '' || !$this->conversationId) return;
        $this->message = '';

        AiMessage::create([
            'ai_conversation_id' => $this->conversationId,
            'role' => 'user',
            'content' => $text,
            'provider_used' => null,
            'model_used' => null,
            'tokens_used' => null,
            'cost' => null,
            'response_time_ms' => null,
        ]);

        $qLower = strtolower($text);
        if (str_contains($qLower, 'human') || str_contains($qLower, 'agent') || str_contains($qLower, 'support')) {
            $this->needsHuman = true;
        }

        $kb = $rag->retrieve($text, 4);
        $kbBlock = $this->formatKbContext($kb);

        $defaultSystem = <<<'SYS'
You are the client-facing assistant for this agency platform.
You must not reveal other clients' data or internal admin-only information.
You can answer FAQs and explain how to use the portal (requests, invoices, documents, messages).
If the user requests account changes, billing changes, or anything you cannot verify, ask to contact support.
Be friendly and concise.
SYS;

        $system = $templates->systemPrompt('client_assistant_system', [], $defaultSystem);

        $history = $this->promptHistory(12);
        $messages = array_merge(
            [['role' => 'system', 'content' => $system . "\n\nKnowledge base:\n" . $kbBlock]],
            $history,
            [['role' => 'user', 'content' => $text]]
        );

        try {
            $target = $providers->routeToOptimalProvider('client_assistant', 'low');
            $res = $providers->withFallback($target['provider'], function ($provider) use ($messages, $target) {
                return $provider->chat($messages, [
                    'task_type' => 'client_assistant',
                    'timeout' => 90,
                    'model' => $target['model'],
                    'ai_conversation_id' => $this->conversationId,
                    'user_id' => Auth::id(),
                    'client_id' => Auth::user()?->client_id,
                ]);
            }, 'client_assistant');

            $assistantText = trim((string) ($res['text'] ?? ''));
            if ($assistantText === '') {
                $assistantText = 'Sorry — I couldn’t generate a response. Please try again.';
            }

            if (str_contains(strtolower($assistantText), 'contact support') || str_contains(strtolower($assistantText), 'human')) {
                $this->needsHuman = true;
            }

            $tokens = (array) ($res['tokens'] ?? []);
            $tokensUsed = (int) ($tokens['total'] ?? ((int) ($tokens['input'] ?? 0) + (int) ($tokens['output'] ?? 0)));

            AiMessage::create([
                'ai_conversation_id' => $this->conversationId,
                'role' => 'assistant',
                'content' => $assistantText,
                'provider_used' => $res['provider'] ?? $target['provider'],
                'model_used' => $res['model'] ?? $target['model'],
                'tokens_used' => $tokensUsed > 0 ? $tokensUsed : null,
                'cost' => $res['estimated_cost'] ?? null,
                'response_time_ms' => isset($res['response_time_ms']) ? (int) $res['response_time_ms'] : null,
            ]);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            AiMessage::create([
                'ai_conversation_id' => $this->conversationId,
                'role' => 'assistant',
                'content' => 'Sorry — the assistant is currently unavailable. Please try again later.',
                'provider_used' => 'system',
                'model_used' => null,
                'tokens_used' => null,
                'cost' => null,
                'response_time_ms' => null,
            ]);
        }

        $this->loadConversation();
    }

    public function feedback(int $messageId, string $rating): void
    {
        $this->authorizeClient();
        if (!in_array($rating, ['up', 'down'], true)) return;

        $msg = AiMessage::query()->findOrFail($messageId);
        AiMessageFeedback::create([
            'ai_message_id' => $msg->id,
            'user_id' => Auth::id(),
            'client_id' => Auth::user()?->client_id,
            'rating' => $rating,
            'helpful' => $rating === 'up',
            'comment' => null,
            'edited_text' => null,
            'meta' => ['surface' => 'client_assistant_chat'],
        ]);
        session()->flash('success', 'Thanks for the feedback.');
    }

    protected function promptHistory(int $maxMessages): array
    {
        if (!$this->conversationId) return [];
        $rows = AiMessage::query()
            ->where('ai_conversation_id', $this->conversationId)
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('id')
            ->limit(max(1, $maxMessages))
            ->get()
            ->reverse()
            ->values();

        return $rows->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->all();
    }

    protected function formatKbContext(array $kb): string
    {
        if (empty($kb)) return '(none)';
        $lines = [];
        foreach ($kb as $row) {
            $doc = $row['document'];
            $lines[] = '---';
            $lines[] = ($doc->title ?: $doc->original_filename);
            $lines[] = 'Snippet: ' . trim((string) $row['snippet']);
        }
        return implode("\n", $lines);
    }

    protected function authorizeClient(): void
    {
        $u = Auth::user();
        if (!$u) abort(403);
    }

    public function render()
    {
        $this->authorizeClient();

        return view('livewire.ai.client-assistant-chat')
            ->layout('layouts.app', ['title' => 'AI Assistant']);
    }
}


<?php

namespace App\Http\Livewire\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiMessageFeedback;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Request as ServiceRequest;
use App\Services\AI\AdminAssistantToolingService;
use App\Services\AI\AIProviderManager;
use App\Services\AI\AISafetyService;
use App\Services\AI\KnowledgeBaseRagService;
use App\Services\AI\PromptTemplateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AIAssistantChat extends Component
{
    public ?int $conversationId = null;
    public string $message = '';

    /** @var array<int, array<string,mixed>> */
    public array $messages = [];

    public ?string $error = null;

    /** @var array<int, string> */
    public array $edits = [];

    // Context (optional)
    public ?int $clientId = null;
    public ?int $requestId = null;
    public ?int $invoiceId = null;

    public function mount(?int $conversation = null): void
    {
        $this->authorizeAdmin();

        $this->conversationId = $conversation;
        $this->clientId = request()->query('client_id') ? (int) request()->query('client_id') : null;
        $this->requestId = request()->query('request_id') ? (int) request()->query('request_id') : null;
        $this->invoiceId = request()->query('invoice_id') ? (int) request()->query('invoice_id') : null;

        if ($this->conversationId) {
            $this->loadConversation();
        } else {
            $conv = AiConversation::create([
                'client_id' => null,
                'user_id' => Auth::id(),
                'context_type' => $this->requestId ? 'request' : ($this->invoiceId ? 'invoice' : 'general'),
                'context_id' => $this->requestId ?: ($this->invoiceId ?: null),
                'title' => 'Admin assistant chat',
            ]);
            $this->conversationId = $conv->id;
            $this->loadConversation();
        }
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
            'provider_used' => $m->provider_used,
            'model_used' => $m->model_used,
            'cost' => $m->cost,
            'created_at' => $m->created_at?->toDateTimeString(),
        ])->all();

        foreach ($rows as $m) {
            if ($m->role === 'assistant') {
                $this->edits[$m->id] = $this->edits[$m->id] ?? $m->content;
            }
        }
    }

    public function send(
        AIProviderManager $providers,
        AISafetyService $safety,
        PromptTemplateService $templates,
        KnowledgeBaseRagService $rag,
        AdminAssistantToolingService $tools
    ): void {
        $this->authorizeAdmin();

        $this->error = null;
        $text = trim($this->message);
        if ($text === '' || !$this->conversationId) return;

        $this->message = '';

        // Persist user message
        $userMsg = AiMessage::create([
            'ai_conversation_id' => $this->conversationId,
            'role' => 'user',
            'content' => $text,
            'provider_used' => null,
            'model_used' => null,
            'tokens_used' => null,
            'cost' => null,
            'response_time_ms' => null,
        ]);

        // Try deterministic tool handling first.
        $toolRes = $tools->tryHandle($text, [
            'client_id' => $this->clientId,
            'request_id' => $this->requestId,
            'invoice_id' => $this->invoiceId,
        ]);
        if ($toolRes['handled']) {
            AiMessage::create([
                'ai_conversation_id' => $this->conversationId,
                'role' => 'assistant',
                'content' => $toolRes['answer'],
                'provider_used' => 'system',
                'model_used' => null,
                'tokens_used' => null,
                'cost' => 0,
                'response_time_ms' => null,
            ]);
            $this->loadConversation();
            return;
        }

        $contextSummary = $this->contextSummary();
        $kb = $rag->retrieve($text, 4);
        $kbBlock = $this->formatKbContext($kb);

        $defaultSystem = <<<'SYS'
You are the admin operations assistant for this agency platform.
Be concise and actionable. If you are unsure, ask a clarifying question.
If asked to perform an action that changes data, describe the action and ask for confirmation unless it is explicitly allowed.
SYS;

        $system = $templates->systemPrompt('admin_assistant_system', [
            'context_summary' => $contextSummary,
        ], $defaultSystem);

        $history = $this->promptHistory(16);

        $messages = array_merge(
            [['role' => 'system', 'content' => $system . "\n\nContext:\n" . $contextSummary . "\n\nKnowledge base:\n" . $kbBlock]],
            $history,
            [['role' => 'user', 'content' => $text]]
        );

        try {
            $target = $providers->routeToOptimalProvider('admin_assistant', 'high');
            $res = $safety->safeChat($messages, [
                'provider' => $target['provider'],
                'model' => $target['model'],
                'task_type' => 'admin_assistant',
                'timeout' => 120,
                'ai_conversation_id' => $this->conversationId,
                'user_id' => Auth::id(),
                'user_query' => $text,
            ]);

            $assistantText = (string) ($res['text'] ?? '');
            $tokens = (array) ($res['tokens'] ?? []);
            $tokensUsed = (int) ($tokens['total'] ?? ((int) ($tokens['input'] ?? 0) + (int) ($tokens['output'] ?? 0)));

            AiMessage::create([
                'ai_conversation_id' => $this->conversationId,
                'role' => 'assistant',
                'content' => $assistantText !== '' ? $assistantText : '(no response)',
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
                'content' => 'Error: ' . $e->getMessage(),
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
        $this->authorizeAdmin();
        if (!in_array($rating, ['up', 'down'], true)) return;

        $msg = AiMessage::query()->findOrFail($messageId);
        AiMessageFeedback::create([
            'ai_message_id' => $msg->id,
            'user_id' => Auth::id(),
            'client_id' => null,
            'rating' => $rating,
            'helpful' => $rating === 'up',
            'comment' => null,
            'edited_text' => null,
            'meta' => ['surface' => 'admin_assistant_chat'],
        ]);
        session()->flash('success', 'Feedback recorded.');
    }

    public function saveEdit(int $messageId): void
    {
        $this->authorizeAdmin();

        $msg = AiMessage::query()->findOrFail($messageId);
        if ($msg->role !== 'assistant') return;

        $edited = trim((string) ($this->edits[$messageId] ?? ''));
        if ($edited === '' || $edited === $msg->content) {
            session()->flash('success', 'No changes to save.');
            return;
        }

        AiMessageFeedback::create([
            'ai_message_id' => $msg->id,
            'user_id' => Auth::id(),
            'client_id' => null,
            'rating' => null,
            'helpful' => null,
            'comment' => 'Admin edited AI output',
            'edited_text' => $edited,
            'meta' => ['surface' => 'admin_assistant_chat', 'original_preview' => substr($msg->content, 0, 500)],
        ]);

        session()->flash('success', 'Edit captured for training.');
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
        foreach ($kb as $i => $row) {
            $doc = $row['document'];
            $lines[] = '---';
            $lines[] = 'Doc #' . $doc->id . ': ' . ($doc->title ?: $doc->original_filename);
            $lines[] = 'Snippet: ' . trim((string) $row['snippet']);
        }
        return implode("\n", $lines);
    }

    protected function contextSummary(): string
    {
        $parts = [];
        if ($this->clientId) {
            $c = Client::query()->find($this->clientId);
            if ($c) $parts[] = "Client: {$c->company_name} (#{$c->id})";
        }
        if ($this->requestId) {
            $r = ServiceRequest::query()->with('client')->find($this->requestId);
            if ($r) $parts[] = "Request: {$r->title} (#{$r->id}), status={$r->status}, client={$r->client?->company_name}";
        }
        if ($this->invoiceId) {
            $inv = Invoice::query()->with('client')->find($this->invoiceId);
            if ($inv) $parts[] = "Invoice: {$inv->invoice_number} (#{$inv->id}), status={$inv->status}, amount={$inv->amount}, client={$inv->client?->company_name}";
        }
        return empty($parts) ? '(no specific page context)' : implode("\n", $parts);
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (!$u || !$u->can('access admin panel')) {
            abort(403);
        }
    }

    public function render()
    {
        $this->authorizeAdmin();

        return view('livewire.ai.assistant-chat', [
            'conversationId' => $this->conversationId,
        ])->layout('layouts.admin', ['title' => 'AI Assistant']);
    }
}


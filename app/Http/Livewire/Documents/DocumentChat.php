<?php

namespace App\Http\Livewire\Documents;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Document;
use App\Services\AI\AIProviderManager;
use App\Services\AI\DocumentEmbeddingService;
use App\Services\AI\DocumentSemanticSearchService;
use App\Services\AI\DocumentTextExtractor;
use Livewire\Component;

class DocumentChat extends Component
{
    public ?Document $document = null; // optional: restrict to single doc

    public string $question = '';
    public array $messages = [];

    public ?AiConversation $conversation = null;

    public function mount(?Document $document = null): void
    {
        if ($document) {
            $this->authorizeAccess($document);
            $this->document = $document;
        }

        $this->conversation = AiConversation::create([
            'client_id' => auth()->user()?->client_id,
            'user_id' => auth()->id(),
            'context_type' => $document ? 'document' : 'general',
            'context_id' => $document?->id,
            'title' => $document ? ('Chat: ' . ($document->title ?? $document->original_filename)) : 'Document chat',
        ]);

        $this->messages = [];
    }

    protected function authorizeAccess(Document $doc): void
    {
        $user = auth()->user();
        if ($user && $user->isClient() && (int) $doc->client_id !== (int) $user->client_id) {
            abort(403);
        }
    }

    public function send(AIProviderManager $ai, DocumentEmbeddingService $emb, DocumentSemanticSearchService $search, DocumentTextExtractor $extractor): void
    {
        $q = trim($this->question);
        if ($q === '') return;

        $this->messages[] = ['role' => 'user', 'content' => $q];
        AiMessage::create([
            'ai_conversation_id' => $this->conversation->id,
            'role' => 'user',
            'content' => $q,
        ]);

        $context = $this->buildContext($q, $emb, $search, $extractor);

        $system = "You are a helpful assistant. Answer questions using ONLY the provided document context. If context is insufficient, say what is missing.";

        $res = $ai->withFallback($this->document ? 'claude' : 'openai', function ($provider) use ($system, $q, $context) {
            return $provider->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Context:\n{$context}\n\nQuestion:\n{$q}"],
            ], [
                'task_type' => 'document_chat',
                'timeout' => 120,
            ]);
        }, 'document_chat');

        $answer = (string) ($res['text'] ?? '');
        $this->messages[] = ['role' => 'assistant', 'content' => $answer];
        AiMessage::create([
            'ai_conversation_id' => $this->conversation->id,
            'role' => 'assistant',
            'content' => $answer,
            'provider_used' => $res['provider'] ?? null,
            'model_used' => $res['model'] ?? null,
            'tokens_used' => $res['tokens']['total'] ?? null,
            'cost' => $res['estimated_cost'] ?? null,
        ]);

        $this->question = '';
    }

    protected function buildContext(string $question, DocumentEmbeddingService $emb, DocumentSemanticSearchService $search, DocumentTextExtractor $extractor): string
    {
        // If single document chat, just extract a chunk from that doc.
        if ($this->document) {
            $ex = $extractor->extractFromStorage('documents', (string) $this->document->file_path, $this->document->mime_type, $this->document->original_filename);
            $text = (string) ($ex['text'] ?? '');
            return $this->clip($text, 20000);
        }

        // Cross-document: embed question and pick top docs that have embeddings.
        $qVec = $emb->embedText($question, ['provider' => 'openai', 'model' => 'text-embedding-3-small', 'timeout' => 45]);
        if (!$qVec) {
            return '(No embeddings available for semantic retrieval.)';
        }

        $ranked = $search->findSimilarByEmbedding($qVec, 4, 500);
        if (empty($ranked)) {
            return '(No similar documents found.)';
        }

        $docIds = array_map(fn ($r) => (int) $r['document_id'], $ranked);
        $docs = Document::query()->whereIn('id', $docIds)->get()->keyBy('id');

        $parts = [];
        foreach ($ranked as $r) {
            $doc = $docs->get((int) $r['document_id']);
            if (!$doc) continue;
            $this->authorizeAccess($doc);
            $ex = $extractor->extractFromStorage('documents', (string) $doc->file_path, $doc->mime_type, $doc->original_filename);
            $parts[] = "=== Document #{$doc->id} ({$doc->original_filename}) similarity=" . number_format((float) $r['score'], 3) . " ===\n" .
                $this->clip((string) ($ex['text'] ?? ''), 8000);
        }

        return implode("\n\n", $parts);
    }

    protected function clip(string $text, int $maxChars): string
    {
        $text = trim($text);
        if ($text === '') return '(No text extracted from document.)';
        if (mb_strlen($text) <= $maxChars) return $text;
        return mb_substr($text, 0, $maxChars) . "\n\n...(truncated)...";
    }

    public function render()
    {
        return view('livewire.documents.document-chat', [
            'document' => $this->document,
            'messages' => $this->messages,
        ]);
    }
}


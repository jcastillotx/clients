<?php

namespace App\Services\AI;

use App\Models\Document;
use App\Models\DocumentEmbedding;
use Illuminate\Support\Facades\Log;

class DocumentEmbeddingService
{
    public function __construct(
        protected AIProviderManager $providers,
        protected DocumentTextExtractor $extractor
    ) {
    }

    public function contentHash(string $content): string
    {
        return hash('sha256', $content);
    }

    public function contentForEmbedding(Document $document, int $maxChars = 20000): string
    {
        $document->loadMissing(['client']);

        $ex = $this->extractor->extractFromStorage('documents', (string) $document->file_path, $document->mime_type, $document->original_filename);
        $text = trim((string) ($ex['text'] ?? ''));

        // Keep embeddings stable and cheap.
        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
        }

        $prefix = trim(implode("\n", array_filter([
            'Client: ' . ($document->client?->company_name ?? ''),
            'Title: ' . ($document->title ?? ''),
            'Filename: ' . ($document->original_filename ?? ''),
            'Mime: ' . ($document->mime_type ?? ''),
        ])));

        return trim($prefix . "\n\n" . $text);
    }

    /**
     * @return array<int,float>|null
     */
    public function embedText(string $content, array $options = []): ?array
    {
        $preferred = (string) ($options['provider'] ?? 'openai');
        $model = (string) ($options['model'] ?? 'text-embedding-3-small');

        try {
            $res = $this->providers->withFallback($preferred, function ($provider) use ($content, $options, $model) {
                return $provider->generateEmbeddings([$content], [
                    'model' => $model,
                    'task_type' => 'embeddings',
                    'timeout' => (int) ($options['timeout'] ?? 60),
                    'task_id' => $options['task_id'] ?? null,
                ]);
            }, 'document_embeddings');

            $vectors = $res['embeddings'] ?? null;
            if (!is_array($vectors) || !isset($vectors[0]) || !is_array($vectors[0])) {
                return null;
            }

            /** @var array<int,float> $vec */
            $vec = array_map(fn ($v) => (float) $v, $vectors[0]);
            return $vec;
        } catch (\Throwable $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'api key') || str_contains($msg, 'not configured')) {
                return null;
            }
            Log::info('Document embedding generation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function upsertDocumentEmbedding(Document $document, array $options = []): ?DocumentEmbedding
    {
        $provider = (string) ($options['provider'] ?? 'openai');
        $model = (string) ($options['model'] ?? 'text-embedding-3-small');

        $content = $this->contentForEmbedding($document, (int) ($options['max_chars'] ?? 20000));
        $hash = $this->contentHash($content);

        $existing = DocumentEmbedding::query()
            ->where('document_id', $document->id)
            ->where('provider', $provider)
            ->where('model', $model)
            ->orderByDesc('id')
            ->first();

        if ($existing && $existing->content_hash === $hash) {
            return $existing;
        }

        $vec = $this->embedText($content, [
            'provider' => $provider,
            'model' => $model,
            'timeout' => $options['timeout'] ?? null,
            'task_id' => $options['task_id'] ?? null,
        ]);
        if (!$vec) return null;

        return DocumentEmbedding::create([
            'document_id' => $document->id,
            'provider' => $provider,
            'model' => $model,
            'content_hash' => $hash,
            'embedding' => $vec,
        ]);
    }
}


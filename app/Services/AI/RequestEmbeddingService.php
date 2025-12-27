<?php

namespace App\Services\AI;

use App\Models\Request as ServiceRequest;
use App\Models\RequestEmbedding;
use Illuminate\Support\Facades\Log;

class RequestEmbeddingService
{
    public function __construct(
        protected AIProviderManager $providers
    ) {}

    public function contentForEmbedding(ServiceRequest $request): string
    {
        $request->loadMissing(['client']);
        $client = $request->client?->company_name ?? '';

        return trim(implode("\n", array_filter([
            "Client: {$client}",
            "Title: {$request->title}",
            "Description: {$request->description}",
            "Type: {$request->type}",
            "Priority: {$request->priority}",
        ])));
    }

    public function contentHash(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Best-effort: returns embedding vector or null if provider not configured.
     *
     * @return array<int, float>|null
     */
    public function embedText(string $content, array $options = []): ?array
    {
        $preferred = (string) ($options['provider'] ?? 'openai');

        try {
            $res = $this->providers->withFallback($preferred, function ($provider) use ($content) {
                /** @var array<int,float> $vec */
                $vec = $provider->generateEmbeddings($content);

                return ['embedding' => $vec];
            }, 'request_embeddings');

            $vec = $res['embedding'] ?? null;

            return is_array($vec) ? array_map(fn ($v) => (float) $v, $vec) : null;
        } catch (\Throwable $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'api key') || str_contains($msg, 'not configured')) {
                return null;
            }
            Log::info('Embedding generation failed: '.$e->getMessage());

            return null;
        }
    }

    public function upsertRequestEmbedding(ServiceRequest $request, array $options = []): ?RequestEmbedding
    {
        $content = $this->contentForEmbedding($request);
        $hash = $this->contentHash($content);
        $provider = (string) ($options['provider'] ?? 'openai');
        $model = (string) ($options['model'] ?? 'text-embedding-3-small'); // stored for future; providers may ignore

        $existing = RequestEmbedding::query()
            ->where('request_id', $request->id)
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
        if (! $vec) {
            return null;
        }

        return RequestEmbedding::create([
            'request_id' => $request->id,
            'provider' => $provider,
            'model' => $model,
            'content_hash' => $hash,
            'embedding' => $vec,
        ]);
    }
}

<?php

namespace App\Services\AI;

use OpenAI;
use OpenAI\Client;
use RuntimeException;

class OpenAIService extends BaseAIService
{
    protected ?Client $client = null;

    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): static
    {
        parent::configure($config);

        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey !== '') {
            $this->client = OpenAI::client($apiKey);
        } else {
            $this->client = null;
        }

        return $this;
    }

    public function chat(array $messages, array $options = []): array
    {
        if (!$this->client) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'gpt-4o-mini');

        $started = microtime(true);
        $res = $this->client->chat()->create([
            'model' => $model,
            'messages' => $messages,
            // passthrough (temperature, max_tokens, etc.)
            ...array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p', 'presence_penalty', 'frequency_penalty'])),
        ]);
        $ms = (int) round((microtime(true) - $started) * 1000);

        $content = $res->choices[0]->message->content ?? '';
        $usage = $res->usage ?? null;

        return [
            'provider' => 'openai',
            'model' => $model,
            'text' => (string) $content,
            'response_time_ms' => $ms,
            'tokens' => [
                'input' => (int) ($usage?->promptTokens ?? 0),
                'output' => (int) ($usage?->completionTokens ?? 0),
                'total' => (int) ($usage?->totalTokens ?? 0),
            ],
        ];
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        if (!$this->client) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'gpt-4o-mini');

        $stream = $this->client->chat()->createStreamed([
            'model' => $model,
            'messages' => $messages,
            ...array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p', 'presence_penalty', 'frequency_penalty'])),
        ]);

        foreach ($stream as $chunk) {
            $delta = $chunk->choices[0]->delta->content ?? null;
            if ($delta !== null && $delta !== '') {
                yield ['delta' => (string) $delta];
            }
        }
    }

    public function generateEmbeddings(string $text): array
    {
        if (!$this->client) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = (string) ($this->config['embedding_model'] ?? 'text-embedding-3-small');
        $res = $this->client->embeddings()->create([
            'model' => $model,
            'input' => $text,
        ]);

        /** @var array<int, float> $vec */
        $vec = $res->embeddings[0]->embedding ?? [];
        return $vec;
    }

    public function getModelList(): array
    {
        // The OpenAI PHP client may not expose model listing consistently across versions.
        // Keep a small safe default list and allow config/DB to override.
        $defaults = [
            (string) ($this->config['default_model'] ?? 'gpt-4o-mini'),
            (string) ($this->config['embedding_model'] ?? 'text-embedding-3-small'),
        ];

        return array_values(array_unique(array_filter($defaults)));
    }

    public function validateApiKey(): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            // Lightweight call: create a tiny chat completion.
            $this->chat([['role' => 'user', 'content' => 'ping']], ['max_tokens' => 1]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}


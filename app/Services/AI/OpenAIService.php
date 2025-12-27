<?php

namespace App\Services\AI;

use Illuminate\Support\Str;
use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use RuntimeException;

class OpenAIService extends BaseAIService
{
    protected ?Client $client = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function configure(array $config): static
    {
        parent::configure($config);

        $apiKey = (string) ($this->config['api_key'] ?? '');
        $base = (string) ($this->config['api_base'] ?? '');

        if ($apiKey !== '') {
            $factory = OpenAI::factory()->withApiKey($apiKey);
            if ($base !== '') {
                $factory = $factory->withBaseUri($base);
            }
            $this->client = $factory->make();
        } else {
            $this->client = null;
        }

        return $this;
    }

    public function chat(array $messages, array $options = []): array
    {
        if (! $this->client) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'gpt-4o-mini');
        $tools = $options['tools'] ?? $options['functions'] ?? null; // support function/tool calling
        $toolChoice = $options['tool_choice'] ?? null;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            ...array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p', 'presence_penalty', 'frequency_penalty'])),
        ];
        if (is_array($tools)) {
            // Prefer "tools" (modern). If caller passed "functions", treat as tools.
            $payload['tools'] = $tools;
            if ($toolChoice !== null) {
                $payload['tool_choice'] = $toolChoice;
            }
        }

        $started = microtime(true);
        $res = $this->retry(function () use ($payload) {
            return $this->client->chat()->create($payload);
        });
        $ms = (int) round((microtime(true) - $started) * 1000);

        $message = $res->choices[0]->message ?? null;
        $content = $message?->content ?? '';
        $toolCalls = $message?->toolCalls ?? null;
        $usage = $res->usage ?? null;

        $out = [
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
        if ($toolCalls) {
            $out['tool_calls'] = $toolCalls;
        }
        $out['estimated_cost'] = $this->estimateCostForModel($model, $out['tokens']);

        // Best-effort logging/persistence
        $this->recordInteraction([
            'provider' => 'openai',
            'model' => $model,
            'task_type' => $options['task_type'] ?? null,
            'client_id' => $options['client_id'] ?? null,
            'user_id' => $options['user_id'] ?? null,
            'ai_conversation_id' => $options['ai_conversation_id'] ?? null,
            'task_id' => $options['task_id'] ?? null,
            'user_message' => $this->extractUserMessage($messages),
        ], $out);

        return $out;
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        if (! $this->client) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'gpt-4o-mini');
        $tools = $options['tools'] ?? $options['functions'] ?? null;
        $toolChoice = $options['tool_choice'] ?? null;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            ...array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p', 'presence_penalty', 'frequency_penalty'])),
        ];
        if (is_array($tools)) {
            $payload['tools'] = $tools;
            if ($toolChoice !== null) {
                $payload['tool_choice'] = $toolChoice;
            }
        }

        $stream = $this->client->chat()->createStreamed($payload);

        foreach ($stream as $chunk) {
            $delta = $chunk->choices[0]->delta->content ?? null;
            if ($delta !== null && $delta !== '') {
                yield ['delta' => (string) $delta];
            }
        }
    }

    public function generateEmbeddings(string $text): array
    {
        if (! $this->client) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = (string) ($this->config['embedding_model'] ?? 'text-embedding-3-small');
        $res = $this->retry(function () use ($model, $text) {
            return $this->client->embeddings()->create([
                'model' => $model,
                'input' => $text,
            ]);
        });

        /** @var array<int, float> $vec */
        $vec = $res->embeddings[0]->embedding ?? [];

        return $vec;
    }

    public function getModelList(): array
    {
        $defaults = [
            'gpt-4',
            'gpt-4-turbo',
            'gpt-3.5-turbo',
            (string) ($this->config['default_model'] ?? 'gpt-4o-mini'),
            (string) ($this->config['embedding_model'] ?? 'text-embedding-3-small'),
        ];

        // If key is valid, prefer live list (best-effort).
        try {
            if ($this->client) {
                $models = $this->client->models()->list();
                $ids = [];
                foreach ($models->data as $m) {
                    if (! empty($m->id)) {
                        $ids[] = (string) $m->id;
                    }
                }
                if ($ids !== []) {
                    return array_values(array_unique($ids));
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return array_values(array_unique(array_filter($defaults)));
    }

    public function validateApiKey(): bool
    {
        if (! $this->client) {
            return false;
        }

        try {
            // Lightweight: list models.
            $this->client->models()->list();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Cost calculation based on OpenAI pricing table in config.
     *
     * @param  array{input:int, output:int, total:int}  $tokens
     */
    protected function estimateCostForModel(string $model, array $tokens): float
    {
        $pricing = (array) config('ai-providers.pricing.openai', []);

        // Normalize e.g. "gpt-4-0125-preview" -> match by prefix.
        $key = $model;
        if (! array_key_exists($key, $pricing)) {
            foreach ($pricing as $k => $_) {
                if (Str::startsWith($model, $k)) {
                    $key = $k;
                    break;
                }
            }
        }

        $row = (array) ($pricing[$key] ?? []);
        $in = isset($row['input']) ? (float) $row['input'] : (float) ($this->config['cost_per_1k_input_tokens'] ?? 0);
        $out = isset($row['output']) ? (float) $row['output'] : (float) ($this->config['cost_per_1k_output_tokens'] ?? 0);

        return (($tokens['input'] / 1000.0) * $in) + (($tokens['output'] / 1000.0) * $out);
    }

    /**
     * Exponential backoff retry for transient failures (rate limits, timeouts).
     */
    protected function retry(callable $fn)
    {
        $attempts = (int) (($this->config['retries'] ?? 3));

        $sleepMs = 250;
        for ($i = 0; $i < max(1, $attempts); $i++) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $isRate = str_contains($msg, 'Rate limit') || str_contains($msg, '429') || str_contains($msg, 'rate_limit');
                $isTimeout = str_contains(strtolower($msg), 'timeout');

                if ($i >= ($attempts - 1) || (! $isRate && ! $isTimeout && ! ($e instanceof ErrorException))) {
                    throw $e;
                }

                usleep($sleepMs * 1000);
                $sleepMs = min(4000, $sleepMs * 2);
            }
        }

        throw new RuntimeException('OpenAI request failed after retries.');
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    protected function extractUserMessage(array $messages): ?string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? null) === 'user') {
                $c = $messages[$i]['content'] ?? null;

                return is_string($c) ? $c : null;
            }
        }

        return null;
    }
}

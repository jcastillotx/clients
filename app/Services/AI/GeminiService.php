<?php

namespace App\Services\AI;

use GuzzleHttp\Client as HttpClient;
use RuntimeException;

/**
 * Google Gemini provider service.
 *
 * Supports Gemini 1.5 Pro, Flash, and other Google AI models.
 * Uses the Gemini API with generateContent endpoint.
 */
class GeminiService extends BaseAIService
{
    protected ?HttpClient $http = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function configure(array $config): static
    {
        parent::configure($config);

        $this->http = new HttpClient([
            'base_uri' => (string) ($this->config['api_base'] ?? 'https://generativelanguage.googleapis.com'),
            'timeout' => (float) ($this->config['timeout'] ?? 60),
        ]);

        return $this;
    }

    public function chat(array $messages, array $options = []): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'gemini-1.5-flash');

        // Convert OpenAI-style messages to Gemini format
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $m) {
            $role = (string) ($m['role'] ?? '');
            $content = (string) ($m['content'] ?? '');

            if ($role === 'system') {
                $systemInstruction = $content;
                continue;
            }

            $geminiRole = $role === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $content]],
            ];
        }

        $payload = [
            'contents' => $contents,
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        // Generation config
        $genConfig = [];
        if (isset($options['temperature'])) {
            $genConfig['temperature'] = (float) $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $genConfig['maxOutputTokens'] = (int) $options['max_tokens'];
        }
        if (isset($options['top_p'])) {
            $genConfig['topP'] = (float) $options['top_p'];
        }
        if (! empty($genConfig)) {
            $payload['generationConfig'] = $genConfig;
        }

        $started = microtime(true);
        $endpoint = "/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $attempts = (int) (($this->config['retries'] ?? 3));
        $sleepMs = 250;

        for ($i = 0; $i < max(1, $attempts); $i++) {
            try {
                $resp = $this->http->post($endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => (float) ($options['timeout'] ?? $this->config['timeout'] ?? 60),
                ]);

                $ms = (int) round((microtime(true) - $started) * 1000);
                $raw = json_decode((string) $resp->getBody(), true);
                if (! is_array($raw)) {
                    throw new RuntimeException('Gemini returned non-JSON response.');
                }

                $out = $this->normalizeChatResponse($payload, $raw, $ms, $model);
                $out['estimated_cost'] = $this->estimateCostForModel($model, $out['tokens'] ?? []);

                $this->recordInteraction([
                    'provider' => 'gemini',
                    'model' => $model,
                    'task_type' => $options['task_type'] ?? null,
                    'client_id' => $options['client_id'] ?? null,
                    'user_id' => $options['user_id'] ?? null,
                    'ai_conversation_id' => $options['ai_conversation_id'] ?? null,
                    'task_id' => $options['task_id'] ?? null,
                    'user_message' => $this->extractUserMessage($messages),
                ], $out);

                return $out;
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $isRate = str_contains($msg, '429') || str_contains(strtolower($msg), 'rate');
                $is5xx = str_contains($msg, '500') || str_contains($msg, '502') || str_contains($msg, '503');

                if ($i >= ($attempts - 1) || (! $isRate && ! $is5xx)) {
                    throw $e;
                }
                usleep($sleepMs * 1000);
                $sleepMs = min(4000, $sleepMs * 2);
            }
        }

        throw new RuntimeException('Gemini request failed after retries.');
    }

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs, string $model): array
    {
        // Gemini returns: candidates[0].content.parts[0].text
        $text = '';
        if (isset($raw['candidates'][0]['content']['parts']) && is_array($raw['candidates'][0]['content']['parts'])) {
            foreach ($raw['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }

        // Usage metadata
        $usage = $raw['usageMetadata'] ?? [];

        return [
            'provider' => 'gemini',
            'model' => $model,
            'text' => $text,
            'response_time_ms' => $responseTimeMs,
            'tokens' => [
                'input' => (int) ($usage['promptTokenCount'] ?? 0),
                'output' => (int) ($usage['candidatesTokenCount'] ?? 0),
                'total' => (int) ($usage['totalTokenCount'] ?? 0),
            ],
        ];
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'gemini-1.5-flash');

        // Convert messages
        $contents = [];
        $systemInstruction = null;
        foreach ($messages as $m) {
            $role = (string) ($m['role'] ?? '');
            $content = (string) ($m['content'] ?? '');
            if ($role === 'system') {
                $systemInstruction = $content;
                continue;
            }
            $geminiRole = $role === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $content]],
            ];
        }

        $payload = ['contents' => $contents];
        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $endpoint = "/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$apiKey}";

        $resp = $this->http->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
            'stream' => true,
            'timeout' => (float) ($options['timeout'] ?? $this->config['timeout'] ?? 120),
        ]);

        $body = $resp->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(4096);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line = trim($line);

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $json = trim(substr($line, 5));
                if ($json === '') {
                    continue;
                }

                $data = json_decode($json, true);
                if (! is_array($data)) {
                    continue;
                }

                $parts = $data['candidates'][0]['content']['parts'] ?? [];
                foreach ($parts as $part) {
                    if (isset($part['text']) && is_string($part['text']) && $part['text'] !== '') {
                        yield ['delta' => $part['text']];
                    }
                }
            }
        }
    }

    public function generateEmbeddings(string $text): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $model = (string) ($this->config['embedding_model'] ?? 'text-embedding-004');
        $endpoint = "/v1beta/models/{$model}:embedContent?key={$apiKey}";

        $resp = $this->http->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'content' => [
                    'parts' => [['text' => $text]],
                ],
            ],
        ]);

        $raw = json_decode((string) $resp->getBody(), true);

        return $raw['embedding']['values'] ?? [];
    }

    public function getModelList(): array
    {
        return [
            'gemini-2.0-flash-exp',
            'gemini-1.5-pro',
            'gemini-1.5-flash',
            'gemini-1.5-flash-8b',
            'gemini-1.0-pro',
        ];
    }

    public function validateApiKey(): bool
    {
        try {
            $this->chat([['role' => 'user', 'content' => 'Hello']], ['max_tokens' => 5]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array{input?:int, output?:int, total?:int}  $tokens
     */
    protected function estimateCostForModel(string $model, array $tokens): float
    {
        $pricing = (array) config('ai-providers.pricing.gemini', []);
        $row = (array) ($pricing[$model] ?? []);
        $in = isset($row['input']) ? (float) $row['input'] : (float) ($this->config['cost_per_1k_input_tokens'] ?? 0);
        $out = isset($row['output']) ? (float) $row['output'] : (float) ($this->config['cost_per_1k_output_tokens'] ?? 0);

        $t = ['input' => (int) ($tokens['input'] ?? 0), 'output' => (int) ($tokens['output'] ?? 0)];

        return (($t['input'] / 1000.0) * $in) + (($t['output'] / 1000.0) * $out);
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

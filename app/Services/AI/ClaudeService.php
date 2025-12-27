<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * Anthropic Claude provider (Anthropic Messages API).
 *
 * Supports multi-turn conversations and system prompts.
 * Streaming is implemented via SSE when `streamChat()` is used.
 */
class ClaudeService extends HttpJsonProviderService
{
    protected function authHeaders(): array
    {
        // Anthropic typically uses: x-api-key + anthropic-version
        return [
            'x-api-key' => (string) ($this->config['api_key'] ?? ''),
            'anthropic-version' => (string) ($this->config['anthropic_version'] ?? '2023-06-01'),
        ];
    }

    protected function chatEndpoint(): string
    {
        // Many Anthropic SDKs use /v1/messages.
        return '/v1/messages';
    }

    public function chat(array $messages, array $options = []): array
    {
        // Convert OpenAI-style messages to Anthropic Messages payload.
        $system = '';
        $anthropicMessages = [];

        foreach ($messages as $m) {
            $role = (string) ($m['role'] ?? '');
            $content = (string) ($m['content'] ?? '');
            if ($role === 'system') {
                $system = trim($system."\n\n".$content);

                continue;
            }
            // Anthropic expects roles: user|assistant
            $anthropicRole = $role === 'assistant' ? 'assistant' : 'user';
            $anthropicMessages[] = ['role' => $anthropicRole, 'content' => $content];
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'claude-3-5-sonnet-latest');
        $maxTokens = (int) ($options['max_tokens'] ?? 1024);

        // Override the HTTP base chat payload expected by parent:
        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $anthropicMessages,
        ];
        if ($system !== '') {
            $payload['system'] = $system;
        }

        // Use the HttpJsonProviderService retry logic, but its payload builder is OpenAI-ish.
        // We call its internal HTTP client directly by temporarily reusing normalizeChatResponse().
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Claude API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $attempts = (int) (($this->config['retries'] ?? 3));
        $sleepMs = 250;
        $started = microtime(true);

        for ($i = 0; $i < max(1, $attempts); $i++) {
            try {
                $resp = $this->http->post($this->chatEndpoint(), [
                    'headers' => array_merge([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ], $this->authHeaders()),
                    'json' => $payload,
                    'timeout' => (float) ($options['timeout'] ?? $this->config['timeout'] ?? 60),
                ]);

                $ms = (int) round((microtime(true) - $started) * 1000);
                $raw = json_decode((string) $resp->getBody(), true);
                if (! is_array($raw)) {
                    throw new RuntimeException('Claude returned non-JSON response.');
                }

                $out = $this->normalizeChatResponse($payload, $raw, $ms);
                $out['estimated_cost'] = $this->estimateCostForModel($model, $out['tokens'] ?? []);

                $this->recordInteraction([
                    'provider' => 'claude',
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

        throw new RuntimeException('Claude request failed after retries.');
    }

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array
    {
        // Anthropic Messages API returns: content:[{type:'text', text:'...'}], usage:{input_tokens, output_tokens}
        $text = '';
        if (isset($raw['content']) && is_array($raw['content'])) {
            foreach ($raw['content'] as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'text' && isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }

        return [
            'provider' => 'claude',
            'model' => (string) ($payload['model'] ?? $this->config['default_model'] ?? ''),
            'text' => $text,
            'response_time_ms' => $responseTimeMs,
            'tokens' => [
                'input' => (int) ($raw['usage']['input_tokens'] ?? 0),
                'output' => (int) ($raw['usage']['output_tokens'] ?? 0),
                'total' => (int) (($raw['usage']['input_tokens'] ?? 0) + ($raw['usage']['output_tokens'] ?? 0)),
            ],
        ];
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        // SSE streaming from Anthropic: stream=true
        $system = '';
        $anthropicMessages = [];
        foreach ($messages as $m) {
            $role = (string) ($m['role'] ?? '');
            $content = (string) ($m['content'] ?? '');
            if ($role === 'system') {
                $system = trim($system."\n\n".$content);

                continue;
            }
            $anthropicMessages[] = ['role' => $role === 'assistant' ? 'assistant' : 'user', 'content' => $content];
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'claude-3-5-sonnet-latest');
        $maxTokens = (int) ($options['max_tokens'] ?? 1024);

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $anthropicMessages,
            'stream' => true,
        ];
        if ($system !== '') {
            $payload['system'] = $system;
        }

        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Claude API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $resp = $this->http->post($this->chatEndpoint(), [
            'headers' => array_merge([
                'Accept' => 'text/event-stream',
                'Content-Type' => 'application/json',
            ], $this->authHeaders()),
            'json' => $payload,
            'stream' => true,
            'timeout' => (float) ($options['timeout'] ?? $this->config['timeout'] ?? 120),
        ]);

        $body = $resp->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(4096);
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                // Extract "data: {json}" lines
                foreach (explode("\n", $event) as $line) {
                    $line = trim($line);
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }
                    $json = trim(substr($line, 5));
                    if ($json === '' || $json === '[DONE]') {
                        continue;
                    }
                    $data = json_decode($json, true);
                    if (! is_array($data)) {
                        continue;
                    }

                    // Anthropic stream delta type: content_block_delta with delta.text
                    if (($data['type'] ?? '') === 'content_block_delta') {
                        $delta = $data['delta']['text'] ?? null;
                        if (is_string($delta) && $delta !== '') {
                            yield ['delta' => $delta];
                        }
                    }
                }
            }
        }
    }

    public function generateEmbeddings(string $text): array
    {
        throw new RuntimeException('Claude embeddings not implemented yet.');
    }

    /**
     * Cost calculation based on configured pricing table.
     *
     * @param  array{input?:int, output?:int, total?:int}  $tokens
     */
    protected function estimateCostForModel(string $model, array $tokens): float
    {
        $pricing = (array) config('ai-providers.pricing.claude', []);
        $row = (array) ($pricing[$model] ?? []);
        $in = isset($row['input']) ? (float) $row['input'] : (float) ($this->config['cost_per_1k_input_tokens'] ?? 0);
        $out = isset($row['output']) ? (float) $row['output'] : (float) ($this->config['cost_per_1k_output_tokens'] ?? 0);

        $t = ['input' => (int) ($tokens['input'] ?? 0), 'output' => (int) ($tokens['output'] ?? 0)];

        return (($t['input'] / 1000.0) * $in) + (($t['output'] / 1000.0) * $out);
    }
}

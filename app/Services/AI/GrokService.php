<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * xAI Grok provider service.
 *
 * Grok uses an OpenAI-compatible API format.
 * Supports Grok-2 and other xAI models.
 */
class GrokService extends HttpJsonProviderService
{
    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . (string) ($this->config['api_key'] ?? ''),
        ];
    }

    protected function chatEndpoint(): string
    {
        return '/v1/chat/completions';
    }

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array
    {
        $text = (string) (($raw['choices'][0]['message']['content'] ?? '') ?: '');
        $usage = (array) ($raw['usage'] ?? []);

        return [
            'provider' => 'grok',
            'model' => (string) ($payload['model'] ?? $this->config['default_model'] ?? ''),
            'text' => $text,
            'response_time_ms' => $responseTimeMs,
            'tokens' => [
                'input' => (int) ($usage['prompt_tokens'] ?? 0),
                'output' => (int) ($usage['completion_tokens'] ?? 0),
                'total' => (int) ($usage['total_tokens'] ?? 0),
            ],
        ];
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Grok API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'grok-2-latest');

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
            ...array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p'])),
        ];

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

                    $delta = $data['choices'][0]['delta']['content'] ?? null;
                    if (is_string($delta) && $delta !== '') {
                        yield ['delta' => $delta];
                    }
                }
            }
        }
    }

    public function getModelList(): array
    {
        return [
            'grok-2-latest',
            'grok-2-1212',
            'grok-2-vision-1212',
            'grok-beta',
        ];
    }
}

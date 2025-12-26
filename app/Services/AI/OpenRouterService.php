<?php

namespace App\Services\AI;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * OpenRouter provider (stub, JSON-over-HTTP).
 *
 * OpenRouter is largely OpenAI-compatible; you can use /chat/completions with
 * Authorization: Bearer <key>.
 */
class OpenRouterService extends HttpJsonProviderService
{
    /**
     * Choose a model dynamically based on task requirements.
     */
    public function chooseModel(array $options = []): string
    {
        $explicit = $options['model'] ?? null;
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $complexity = (string) ($options['complexity'] ?? 'low');
        $map = (array) config('ai-providers.routing.complexity_models.' . $complexity, []);
        $m = $map['openrouter'] ?? null;
        if (is_string($m) && $m !== '') {
            return $m;
        }

        return (string) ($this->config['default_model'] ?? 'openai/gpt-4o-mini');
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . (string) ($this->config['api_key'] ?? ''),
        ];
    }

    protected function chatEndpoint(): string
    {
        return '/chat/completions';
    }

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array
    {
        $text = (string) (($raw['choices'][0]['message']['content'] ?? '') ?: ($raw['choices'][0]['delta']['content'] ?? ''));
        $usage = (array) ($raw['usage'] ?? []);

        return [
            'provider' => 'openrouter',
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

    public function chat(array $messages, array $options = []): array
    {
        // Inject chosen model and let base HTTP handler do retries + logging.
        $options['model'] = $this->chooseModel($options);
        return parent::chat($messages, $options);
    }

    /**
     * Stream OpenAI-compatible SSE stream.
     */
    public function streamChat(array $messages, array $options = []): \Generator
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('OpenRouter API key is not configured.');
        }
        if (!$this->http) {
            $this->configure($this->config);
        }

        $model = $this->chooseModel($options);
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
        ];
        $payload = array_merge($payload, array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p'])));

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

        while (!$body->eof()) {
            $buffer .= $body->read(4096);
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                foreach (explode("\n", $event) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data:')) continue;
                    $json = trim(substr($line, 5));
                    if ($json === '' || $json === '[DONE]') continue;
                    $data = json_decode($json, true);
                    if (!is_array($data)) continue;
                    $delta = $data['choices'][0]['delta']['content'] ?? null;
                    if (is_string($delta) && $delta !== '') {
                        yield ['delta' => $delta];
                    }
                }
            }
        }
    }
}


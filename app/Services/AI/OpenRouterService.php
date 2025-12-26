<?php

namespace App\Services\AI;

/**
 * OpenRouter provider (stub, JSON-over-HTTP).
 *
 * OpenRouter is largely OpenAI-compatible; you can use /chat/completions with
 * Authorization: Bearer <key>.
 */
class OpenRouterService extends HttpJsonProviderService
{
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
}


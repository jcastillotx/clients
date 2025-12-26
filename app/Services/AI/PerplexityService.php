<?php

namespace App\Services\AI;

/**
 * Perplexity provider (stub, JSON-over-HTTP).
 *
 * Many Perplexity endpoints are OpenAI-compatible. Adjust base URI and headers
 * to match your plan and API version.
 */
class PerplexityService extends HttpJsonProviderService
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
        $text = (string) (($raw['choices'][0]['message']['content'] ?? '') ?: '');
        $usage = (array) ($raw['usage'] ?? []);

        return [
            'provider' => 'perplexity',
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


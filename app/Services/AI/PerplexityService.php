<?php

namespace App\Services\AI;

/**
 * Perplexity provider (web-grounded research).
 *
 * Many Perplexity endpoints are OpenAI-compatible. Adjust base URI and headers
 * to match your plan and API version.
 */
class PerplexityService extends HttpJsonProviderService
{
    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.(string) ($this->config['api_key'] ?? ''),
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
        $citations = $raw['citations'] ?? $raw['sources'] ?? null;

        return [
            'provider' => 'perplexity',
            'model' => (string) ($payload['model'] ?? $this->config['default_model'] ?? ''),
            'text' => $text,
            'citations' => is_array($citations) ? $citations : null,
            'response_time_ms' => $responseTimeMs,
            'tokens' => [
                'input' => (int) ($usage['prompt_tokens'] ?? 0),
                'output' => (int) ($usage['completion_tokens'] ?? 0),
                'total' => (int) ($usage['total_tokens'] ?? 0),
            ],
        ];
    }

    /**
     * Research query with citations/sources.
     *
     * @return array{answer:string, sources:?array<int, mixed>, raw:array<string, mixed>}
     */
    public function researchQuery(string $query, array $options = []): array
    {
        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? 'sonar');
        $res = $this->chat([
            ['role' => 'system', 'content' => (string) ($options['system'] ?? 'Provide a web-grounded answer with citations.')],
            ['role' => 'user', 'content' => $query],
        ], [
            ...$options,
            'model' => $model,
        ]);

        return [
            'answer' => (string) ($res['text'] ?? ''),
            'sources' => isset($res['citations']) && is_array($res['citations']) ? $res['citations'] : null,
            'raw' => $res,
        ];
    }
}

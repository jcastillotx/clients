<?php

namespace App\Services\AI;

/**
 * AskSage provider (stub).
 *
 * AskSage APIs vary by plan; this is a placeholder that assumes a chat-style JSON endpoint.
 */
class AskSageService extends HttpJsonProviderService
{
    protected function authHeaders(): array
    {
        // Adjust header scheme to AskSage docs (Bearer is placeholder).
        return [
            'Authorization' => 'Bearer ' . (string) ($this->config['api_key'] ?? ''),
        ];
    }

    protected function chatEndpoint(): string
    {
        // Placeholder path; adjust to AskSage docs.
        return '/chat';
    }

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array
    {
        $text = (string) (($raw['text'] ?? '') ?: ($raw['choices'][0]['message']['content'] ?? ''));

        return [
            'provider' => 'asksage',
            'model' => (string) ($payload['model'] ?? $this->config['default_model'] ?? ''),
            'text' => $text,
            'response_time_ms' => $responseTimeMs,
            'tokens' => [
                'input' => (int) ($raw['tokens_input'] ?? $raw['usage']['prompt_tokens'] ?? 0),
                'output' => (int) ($raw['tokens_output'] ?? $raw['usage']['completion_tokens'] ?? 0),
                'total' => (int) ($raw['tokens_total'] ?? $raw['usage']['total_tokens'] ?? 0),
            ],
        ];
    }
}


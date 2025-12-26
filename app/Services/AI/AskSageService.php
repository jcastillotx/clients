<?php

namespace App\Services\AI;

/**
 * AskSage provider (implementation via configurable HTTP JSON).
 *
 * AskSage APIs vary by plan; this implementation is intentionally flexible:
 * - `api_base` and `chat_path` can be configured in DB or config.
 * - auth header defaults to Bearer but can be swapped by overriding `auth_mode`.
 */
class AskSageService extends HttpJsonProviderService
{
    protected function authHeaders(): array
    {
        $key = (string) ($this->config['api_key'] ?? '');
        $mode = (string) ($this->config['auth_mode'] ?? 'bearer'); // bearer|x-api-key

        return match ($mode) {
            'x-api-key' => ['x-api-key' => $key],
            default => ['Authorization' => 'Bearer ' . $key],
        };
    }

    protected function chatEndpoint(): string
    {
        return (string) ($this->config['chat_path'] ?? '/chat');
    }

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array
    {
        // Handle common shapes:
        // - { text: "...", ... }
        // - OpenAI compatible: { choices: [{ message: { content: "..." } }], usage: {...} }
        $text = (string) (($raw['text'] ?? '') ?: ($raw['response'] ?? '') ?: ($raw['choices'][0]['message']['content'] ?? ''));

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


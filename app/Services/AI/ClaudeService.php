<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * Anthropic Claude provider (stub).
 *
 * This is a placeholder implementation. Wire it to the Anthropic Messages API
 * when you add the final payload/response format you want to support.
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

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array
    {
        // Placeholder normalization.
        $text = '';
        if (isset($raw['content'][0]['text']) && is_string($raw['content'][0]['text'])) {
            $text = $raw['content'][0]['text'];
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
        throw new RuntimeException('Claude streaming not implemented yet.');
    }

    public function generateEmbeddings(string $text): array
    {
        throw new RuntimeException('Claude embeddings not implemented yet.');
    }
}


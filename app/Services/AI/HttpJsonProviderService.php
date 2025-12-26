<?php

namespace App\Services\AI;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 * Base class for providers that use JSON-over-HTTP (OpenRouter, Perplexity, AskSage, etc.)
 *
 * Concrete providers should implement:
 * - authHeaders()
 * - chatEndpoint()
 * - normalizeChatResponse()
 */
abstract class HttpJsonProviderService extends BaseAIService
{
    protected ?HttpClient $http = null;

    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): static
    {
        parent::configure($config);

        $base = (string) ($this->config['api_base'] ?? '');
        $this->http = new HttpClient([
            'base_uri' => $base ?: null,
            'timeout' => (float) ($this->config['timeout'] ?? 30),
        ]);

        return $this;
    }

    abstract protected function authHeaders(): array;

    abstract protected function chatEndpoint(): string;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    abstract protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array;

    public function chat(array $messages, array $options = []): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Provider API key is not configured.');
        }
        if (!$this->http) {
            $this->configure($this->config);
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? '');

        $payload = [
            'model' => $model ?: null,
            'messages' => $messages,
        ];

        $payload = array_merge($payload, array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p'])));

        $started = microtime(true);
        try {
            $resp = $this->http->post($this->chatEndpoint(), [
                'headers' => array_merge([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ], $this->authHeaders()),
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('AI provider request failed: ' . $e->getMessage(), 0, $e);
        }
        $ms = (int) round((microtime(true) - $started) * 1000);

        $rawBody = (string) $resp->getBody();
        $raw = json_decode($rawBody, true);
        if (!is_array($raw)) {
            throw new RuntimeException('AI provider returned non-JSON response.');
        }

        return $this->normalizeChatResponse($payload, $raw, $ms);
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        throw new RuntimeException('Streaming not implemented for this provider yet.');
    }

    public function generateEmbeddings(string $text): array
    {
        throw new RuntimeException('Embeddings not implemented for this provider yet.');
    }

    public function getModelList(): array
    {
        $m = (string) ($this->config['default_model'] ?? '');
        return $m !== '' ? [$m] : [];
    }

    public function validateApiKey(): bool
    {
        try {
            $this->chat([['role' => 'user', 'content' => 'ping']], ['max_tokens' => 1]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}


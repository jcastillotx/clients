<?php

namespace App\Services\AI;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
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
     * @param  array<string, mixed>  $config
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
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    abstract protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array;

    public function chat(array $messages, array $options = []): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Provider API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $model = (string) ($options['model'] ?? $this->config['default_model'] ?? '');

        $payload = [
            'model' => $model ?: null,
            'messages' => $messages,
        ];

        $payload = array_merge($payload, array_intersect_key($options, array_flip(['temperature', 'max_tokens', 'top_p'])));

        $attempts = (int) (($this->config['retries'] ?? 3));
        $sleepMs = 250;

        $started = microtime(true);
        $lastError = null;

        for ($i = 0; $i < max(1, $attempts); $i++) {
            try {
                $resp = $this->http->post($this->chatEndpoint(), [
                    'headers' => array_merge([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ], $this->authHeaders()),
                    'json' => $payload,
                    'timeout' => (float) ($options['timeout'] ?? $this->config['timeout'] ?? 30),
                ]);

                $ms = (int) round((microtime(true) - $started) * 1000);
                $rawBody = (string) $resp->getBody();
                $raw = json_decode($rawBody, true);
                if (! is_array($raw)) {
                    throw new RuntimeException('AI provider returned non-JSON response.');
                }

                $out = $this->normalizeChatResponse($payload, $raw, $ms);
                $out['estimated_cost'] = $out['estimated_cost'] ?? $this->estimateCost($out['tokens'] ?? []);

                $this->recordInteraction([
                    'provider' => $out['provider'] ?? null,
                    'model' => $out['model'] ?? null,
                    'task_type' => $options['task_type'] ?? null,
                    'client_id' => $options['client_id'] ?? null,
                    'user_id' => $options['user_id'] ?? null,
                    'ai_conversation_id' => $options['ai_conversation_id'] ?? null,
                    'task_id' => $options['task_id'] ?? null,
                    'user_message' => $this->extractUserMessage($messages),
                ], $out);

                return $out;
            } catch (RequestException $e) {
                $lastError = $e;

                $status = $e->getResponse()?->getStatusCode();
                $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';

                $shouldRetry = in_array($status, [408, 429], true) || ($status !== null && $status >= 500);

                // Provider-specific error parsing (best-effort)
                if ($body !== '') {
                    Log::warning('AI provider error', [
                        'provider' => static::class,
                        'status' => $status,
                        'body' => $body,
                    ]);
                }

                if (! $shouldRetry || $i >= ($attempts - 1)) {
                    throw new RuntimeException('AI provider request failed: '.$e->getMessage(), (int) ($status ?? 0), $e);
                }

                usleep($sleepMs * 1000);
                $sleepMs = min(4000, $sleepMs * 2);
            } catch (GuzzleException $e) {
                $lastError = $e;
                if ($i >= ($attempts - 1)) {
                    throw new RuntimeException('AI provider request failed: '.$e->getMessage(), 0, $e);
                }
                usleep($sleepMs * 1000);
                $sleepMs = min(4000, $sleepMs * 2);
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        throw new RuntimeException('AI provider request failed after retries.', 0, $lastError instanceof \Throwable ? $lastError : null);
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

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    protected function extractUserMessage(array $messages): ?string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? null) === 'user') {
                $c = $messages[$i]['content'] ?? null;

                return is_string($c) ? $c : null;
            }
        }

        return null;
    }
}

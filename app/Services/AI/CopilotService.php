<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * GitHub Copilot / Microsoft Copilot API provider service.
 *
 * This service integrates with Azure OpenAI Service which powers Copilot.
 * Uses OpenAI-compatible format with Azure endpoints.
 */
class CopilotService extends HttpJsonProviderService
{
    protected function authHeaders(): array
    {
        // Azure OpenAI uses api-key header
        return [
            'api-key' => (string) ($this->config['api_key'] ?? ''),
        ];
    }

    protected function chatEndpoint(): string
    {
        // Azure OpenAI deployment endpoint format
        $deployment = (string) ($this->config['deployment_name'] ?? $this->config['default_model'] ?? 'gpt-4');
        $apiVersion = (string) ($this->config['api_version'] ?? '2024-02-15-preview');

        return "/openai/deployments/{$deployment}/chat/completions?api-version={$apiVersion}";
    }

    protected function normalizeChatResponse(array $payload, array $raw, int $responseTimeMs): array
    {
        $text = (string) (($raw['choices'][0]['message']['content'] ?? '') ?: '');
        $usage = (array) ($raw['usage'] ?? []);

        return [
            'provider' => 'copilot',
            'model' => (string) ($payload['model'] ?? $raw['model'] ?? $this->config['default_model'] ?? ''),
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
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Copilot/Azure API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        // Azure OpenAI doesn't need model in the payload since it's in the URL
        $payload = [
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
                    throw new RuntimeException('Copilot returned non-JSON response.');
                }

                $out = $this->normalizeChatResponse($payload, $raw, $ms);
                $out['estimated_cost'] = $out['estimated_cost'] ?? $this->estimateCost($out['tokens'] ?? []);

                $this->recordInteraction([
                    'provider' => $out['provider'] ?? 'copilot',
                    'model' => $out['model'] ?? null,
                    'task_type' => $options['task_type'] ?? null,
                    'client_id' => $options['client_id'] ?? null,
                    'user_id' => $options['user_id'] ?? null,
                    'ai_conversation_id' => $options['ai_conversation_id'] ?? null,
                    'task_id' => $options['task_id'] ?? null,
                    'user_message' => $this->extractUserMessage($messages),
                ], $out);

                return $out;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $lastError = $e;
                $status = $e->getResponse()?->getStatusCode();
                $shouldRetry = in_array($status, [408, 429], true) || ($status !== null && $status >= 500);

                if (! $shouldRetry || $i >= ($attempts - 1)) {
                    throw new RuntimeException('Copilot request failed: ' . $e->getMessage(), (int) ($status ?? 0), $e);
                }

                usleep($sleepMs * 1000);
                $sleepMs = min(4000, $sleepMs * 2);
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        throw new RuntimeException('Copilot request failed after retries.', 0, $lastError instanceof \Throwable ? $lastError : null);
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Copilot/Azure API key is not configured.');
        }
        if (! $this->http) {
            $this->configure($this->config);
        }

        $payload = [
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
        // Azure OpenAI deployments (these are common deployment names)
        return [
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-35-turbo',
        ];
    }
}

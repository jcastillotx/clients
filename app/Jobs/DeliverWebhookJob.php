<?php

namespace App\Jobs;

use App\Models\WebhookDeliveryLog;
use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public int $endpointId,
        public string $eventType,
        public array $payload,
    ) {
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(): void
    {
        $endpoint = WebhookEndpoint::query()->find($this->endpointId);
        if (!$endpoint || !$endpoint->is_active) {
            return;
        }

        $timestamp = now()->timestamp;
        $body = $this->formatPayload($endpoint, $this->eventType, $this->payload, $timestamp);
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($bodyJson === false) {
            $bodyJson = json_encode(['event' => $this->eventType, 'data' => $this->payload], JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        $signature = hash_hmac('sha256', $timestamp . '.' . $bodyJson, (string) $endpoint->secret);

        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Kre8ivDesigns-Portal-Webhooks/1.0',
            'X-Webhook-Event' => $this->eventType,
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-Signature' => "t={$timestamp},v1={$signature}",
        ], (array) ($endpoint->headers ?? []));

        $started = microtime(true);

        try {
            $resp = Http::timeout(10)
                ->withHeaders($headers)
                ->send('POST', $endpoint->webhook_url, ['body' => $bodyJson]);

            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $ok = $resp->successful();

            WebhookDeliveryLog::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event_type' => $this->eventType,
                'payload' => $body,
                'attempt' => (int) $this->attempts(),
                'succeeded' => $ok,
                'http_status' => $resp->status(),
                'duration_ms' => $durationMs,
                'response_body' => $this->truncate((string) $resp->body(), 20000),
                'error' => null,
                'delivered_at' => $ok ? now() : null,
            ]);

            if (!$ok) {
                throw new \RuntimeException('Webhook delivery failed with status ' . $resp->status());
            }
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            WebhookDeliveryLog::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event_type' => $this->eventType,
                'payload' => $body ?? ['event' => $this->eventType, 'data' => $this->payload],
                'attempt' => (int) $this->attempts(),
                'succeeded' => false,
                'http_status' => null,
                'duration_ms' => $durationMs,
                'response_body' => null,
                'error' => $this->truncate($e->getMessage(), 20000),
                'delivered_at' => null,
            ]);

            throw $e;
        }
    }

    protected function formatPayload(WebhookEndpoint $endpoint, string $event, array $data, int $timestamp): array
    {
        return match ($endpoint->format) {
            'slack' => [
                'text' => "[{$event}] " . ($data['title'] ?? $data['invoice_number'] ?? $data['id'] ?? 'Event'),
            ],
            'teams' => [
                '@type' => 'MessageCard',
                '@context' => 'https://schema.org/extensions',
                'summary' => $event,
                'title' => $event,
                'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
            default => [
                'event' => $event,
                'timestamp' => $timestamp,
                'data' => $data,
            ],
        };
    }

    protected function truncate(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        return mb_substr($s, 0, $max) . '...';
    }
}


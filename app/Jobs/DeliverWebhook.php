<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 20;

    public function __construct(public int $deliveryId) {}

    public function handle(): void
    {
        $delivery = WebhookDelivery::query()->with('endpoint')->find($this->deliveryId);
        if (! $delivery || ! $delivery->endpoint || ! $delivery->endpoint->is_active) {
            return;
        }

        $endpoint = $delivery->endpoint;
        $secret = (string) ($endpoint->secret ?? '');
        $payload = (array) ($delivery->payload ?? []);
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{}';
        }

        $timestamp = now()->getTimestamp();
        $signature = $secret !== '' ? hash_hmac('sha256', $timestamp.'.'.$json, $secret) : '';

        $delivery->update([
            'status' => 'running',
            'attempts' => (int) $delivery->attempts + 1,
            'last_attempt_at' => now(),
            'error_message' => null,
        ]);

        try {
            $resp = Http::timeout(10)
                ->acceptJson()
                ->withHeaders(array_filter([
                    'User-Agent' => config('app.name').' Webhooks',
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $delivery->event_type,
                    'X-Webhook-Delivery' => $delivery->delivery_id,
                    'X-Webhook-Timestamp' => (string) $timestamp,
                    'X-Webhook-Signature' => $signature !== '' ? ('sha256='.$signature) : null,
                ]))
                ->withBody($json, 'application/json')
                ->post($endpoint->webhook_url);

            $ok = $resp->successful();

            $delivery->update([
                'status' => $ok ? 'succeeded' : 'failed',
                'response_status' => $resp->status(),
                'response_body' => mb_substr((string) $resp->body(), 0, 5000),
                'next_attempt_at' => null,
                'error_message' => $ok ? null : ('HTTP '.$resp->status()),
            ]);

            if (! $ok) {
                if ($this->scheduleRetry($delivery)) {
                    $delivery->update(['status' => 'pending']);
                }
            }
        } catch (\Throwable $e) {
            $delivery->update([
                'status' => 'failed',
                'response_status' => null,
                'response_body' => null,
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);

            if ($this->scheduleRetry($delivery)) {
                $delivery->update(['status' => 'pending']);
            }
        }
    }

    protected function scheduleRetry(WebhookDelivery $delivery): bool
    {
        $attempts = (int) $delivery->attempts;
        $max = 5;
        if ($attempts >= $max) {
            $delivery->update(['next_attempt_at' => null]);

            return false;
        }

        $delays = [60, 300, 1800, 7200]; // seconds
        $delay = $delays[min(count($delays) - 1, max(0, $attempts - 1))];

        $next = now()->addSeconds($delay);
        $delivery->update(['next_attempt_at' => $next]);

        $this->release($delay);

        return true;
    }
}

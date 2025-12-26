<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Setting;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Trigger webhooks for a given event and client.
     *
     * @param  array<string,mixed>  $data
     */
    public function triggerWebhook(string $event, array $data, int $clientId): void
    {
        $endpoints = WebhookEndpoint::query()
            ->where('client_id', $clientId)
            ->where('event_type', $event)
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $ep) {
            $deliveryId = (string) Str::uuid();
            $payload = [
                'event' => $event,
                'timestamp' => now()->toISOString(),
                'data' => $data,
            ];

            $delivery = WebhookDelivery::create([
                'webhook_endpoint_id' => $ep->id,
                'delivery_id' => $deliveryId,
                'event_type' => $event,
                'payload' => $payload,
                'status' => 'pending',
                'attempts' => 0,
                'next_attempt_at' => now(),
            ]);

            DeliverWebhook::dispatch($delivery->id)->onQueue('default');
        }

        // Optional: push simple notifications to Slack/Teams if configured.
        $this->notifySlackTeams($event, $data, $clientId);
    }

    public function testWebhook(int $endpointId): ?WebhookDelivery
    {
        $ep = WebhookEndpoint::query()->with('client')->find($endpointId);
        if (!$ep) return null;

        $deliveryId = (string) Str::uuid();
        $payload = [
            'event' => 'test.ping',
            'timestamp' => now()->toISOString(),
            'data' => [
                'message' => 'Webhook test ping',
                'client_id' => $ep->client_id,
                'client' => $ep->client?->company_name,
            ],
        ];

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $ep->id,
            'delivery_id' => $deliveryId,
            'event_type' => 'test.ping',
            'payload' => $payload,
            'status' => 'pending',
            'attempts' => 0,
            'next_attempt_at' => now(),
        ]);

        DeliverWebhook::dispatch($delivery->id)->onQueue('default');
        return $delivery;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    protected function notifySlackTeams(string $event, array $data, int $clientId): void
    {
        $slack = (string) Setting::getValue('notify.slack.webhook', '');
        $teams = (string) Setting::getValue('notify.teams.webhook', '');
        if ($slack === '' && $teams === '') return;

        $summary = $this->summarize($event, $data, $clientId);

        try {
            if ($slack !== '') {
                Http::timeout(5)->post($slack, ['text' => $summary]);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            if ($teams !== '') {
                // Teams incoming webhook expects "text"
                Http::timeout(5)->post($teams, ['text' => $summary]);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * @param  array<string,mixed>  $data
     */
    protected function summarize(string $event, array $data, int $clientId): string
    {
        $id = Arr::get($data, 'id');
        $title = Arr::get($data, 'title') ?: Arr::get($data, 'invoice_number') ?: Arr::get($data, 'document_title');
        $bits = array_filter([
            "Event: {$event}",
            "Client: {$clientId}",
            $id ? ("ID: {$id}") : null,
            $title ? ("Title: {$title}") : null,
        ]);

        return implode(' · ', $bits);
    }
}


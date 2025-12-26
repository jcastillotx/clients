<?php

namespace App\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookEndpoint;

class WebhookService
{
    /**
     * Trigger a webhook event.
     *
     * @param  string  $eventType  e.g. request.created, invoice.paid
     * @param  array  $data  event payload (will be wrapped and signed)
     * @param  int|null  $clientId  optional: restrict to a specific client (plus global endpoints)
     */
    public function triggerWebhook(string $eventType, array $data, ?int $clientId = null): void
    {
        $query = WebhookEndpoint::query()
            ->where('event_type', $eventType)
            ->where('is_active', true);

        if ($clientId !== null) {
            $query->where(function ($q) use ($clientId) {
                $q->whereNull('client_id')->orWhere('client_id', $clientId);
            });
        }

        $endpoints = $query->get(['id']);

        foreach ($endpoints as $ep) {
            DeliverWebhookJob::dispatch($ep->id, $eventType, $data)->onQueue('webhooks');
        }
    }
}


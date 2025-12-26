<?php

namespace App\Observers;

use App\Models\Request as ServiceRequest;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class RequestWebhookObserver
{
    public function __construct(
        protected WebhookService $webhooks,
        protected AutomationEngine $automations,
    ) {}

    public function created(ServiceRequest $req): void
    {
        $payload = [
            'event' => 'request.created',
            'client_id' => $req->client_id,
            'request' => [
                'id' => $req->id,
                'client_id' => $req->client_id,
                'title' => $req->title,
                'status' => $req->status,
                'priority' => $req->priority,
                'type' => $req->type,
                'assigned_to' => $req->assigned_to,
            ],
        ];

        $this->webhooks->triggerWebhook('request.created', [
            'id' => $req->id,
            'client_id' => $req->client_id,
            'title' => $req->title,
            'status' => $req->status,
            'priority' => $req->priority,
            'type' => $req->type,
        ], (int) $req->client_id);

        $this->automations->trigger('request.created', $payload);
    }

    public function updated(ServiceRequest $req): void
    {
        // Assigned trigger
        if ($req->wasChanged('assigned_to')) {
            $payload = [
                'event' => 'request.assigned',
                'client_id' => $req->client_id,
                'request' => [
                    'id' => $req->id,
                    'client_id' => $req->client_id,
                    'title' => $req->title,
                    'status' => $req->status,
                    'priority' => $req->priority,
                    'type' => $req->type,
                    'assigned_to' => $req->assigned_to,
                    'old_assigned_to' => $req->getOriginal('assigned_to'),
                ],
            ];
            $this->automations->trigger('request.assigned', $payload);
        }

        // Status changed trigger (fires for any status change)
        if ($req->wasChanged('status')) {
            $payload = [
                'event' => 'request.status_changed',
                'client_id' => $req->client_id,
                'request' => [
                    'id' => $req->id,
                    'client_id' => $req->client_id,
                    'title' => $req->title,
                    'status' => $req->status,
                    'old_status' => $req->getOriginal('status'),
                    'priority' => $req->priority,
                    'type' => $req->type,
                    'assigned_to' => $req->assigned_to,
                ],
            ];
            $this->automations->trigger('request.status_changed', $payload);
        }

        if ($req->wasChanged('status') && $req->status === 'completed') {
            $payload = [
                'event' => 'request.completed',
                'client_id' => $req->client_id,
                'request' => [
                    'id' => $req->id,
                    'client_id' => $req->client_id,
                    'title' => $req->title,
                    'status' => $req->status,
                    'priority' => $req->priority,
                    'type' => $req->type,
                    'assigned_to' => $req->assigned_to,
                ],
            ];
            $this->webhooks->triggerWebhook('request.completed', [
                'id' => $req->id,
                'client_id' => $req->client_id,
                'title' => $req->title,
            ], (int) $req->client_id);
            $this->automations->trigger('request.completed', $payload);
            return;
        }

        $payload = [
            'event' => 'request.updated',
            'client_id' => $req->client_id,
            'request' => [
                'id' => $req->id,
                'client_id' => $req->client_id,
                'title' => $req->title,
                'status' => $req->status,
                'priority' => $req->priority,
                'type' => $req->type,
                'assigned_to' => $req->assigned_to,
            ],
        ];
        $this->webhooks->triggerWebhook('request.updated', [
            'id' => $req->id,
            'client_id' => $req->client_id,
            'title' => $req->title,
            'status' => $req->status,
        ], (int) $req->client_id);
        $this->automations->trigger('request.updated', $payload);
    }
}


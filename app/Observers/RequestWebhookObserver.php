<?php

namespace App\Observers;

use App\Models\Request as ServiceRequest;
use App\Services\WebhookService;

class RequestWebhookObserver
{
    public function __construct(protected WebhookService $webhooks) {}

    public function created(ServiceRequest $req): void
    {
        $this->webhooks->triggerWebhook('request.created', [
            'id' => $req->id,
            'client_id' => $req->client_id,
            'title' => $req->title,
            'status' => $req->status,
            'priority' => $req->priority,
            'type' => $req->type,
        ], (int) $req->client_id);
    }

    public function updated(ServiceRequest $req): void
    {
        if ($req->wasChanged('status') && $req->status === 'completed') {
            $this->webhooks->triggerWebhook('request.completed', [
                'id' => $req->id,
                'client_id' => $req->client_id,
                'title' => $req->title,
            ], (int) $req->client_id);
            return;
        }

        $this->webhooks->triggerWebhook('request.updated', [
            'id' => $req->id,
            'client_id' => $req->client_id,
            'title' => $req->title,
            'status' => $req->status,
        ], (int) $req->client_id);
    }
}


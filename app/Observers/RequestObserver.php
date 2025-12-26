<?php

namespace App\Observers;

use App\Models\Request as ServiceRequest;
use App\Services\WebhookService;

class RequestObserver
{
    public function created(ServiceRequest $request): void
    {
        app(WebhookService::class)->triggerWebhook('request.created', [
            'id' => $request->id,
            'client_id' => $request->client_id,
            'title' => $request->title,
            'status' => $request->status,
            'priority' => $request->priority,
            'type' => $request->type,
            'created_at' => optional($request->created_at)->toISOString(),
        ], (int) $request->client_id);
    }

    public function updated(ServiceRequest $request): void
    {
        app(WebhookService::class)->triggerWebhook('request.updated', [
            'id' => $request->id,
            'client_id' => $request->client_id,
            'title' => $request->title,
            'status' => $request->status,
            'priority' => $request->priority,
            'type' => $request->type,
            'changes' => $request->getChanges(),
            'updated_at' => optional($request->updated_at)->toISOString(),
        ], (int) $request->client_id);

        if ($request->wasChanged('status') && $request->status === 'completed') {
            app(WebhookService::class)->triggerWebhook('request.completed', [
                'id' => $request->id,
                'client_id' => $request->client_id,
                'title' => $request->title,
                'completed_at' => optional($request->completed_at)->toISOString(),
            ], (int) $request->client_id);
        }
    }
}


<?php

namespace App\Observers;

use App\Jobs\Ai\TriageRequestJob;
use App\Models\Request as ServiceRequest;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class RequestObserver
{
    public function created(ServiceRequest $request): void
    {
        // AI triage workflow (async). Runs best-effort; does nothing if AI isn't configured.
        try {
            // Only triage client-created requests.
            if ((int) ($request->client_id ?? 0) > 0 && (int) ($request->created_by ?? 0) > 0) {
                TriageRequestJob::dispatch($request->id);
            }
        } catch (\Throwable) {
            // ignore
        }

        app(AutomationEngine::class)->run('request.created', [
            'request' => $request->toArray(),
            'client' => $request->client?->toArray(),
        ], (int) $request->client_id);

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
        app(AutomationEngine::class)->run('request.updated', [
            'request' => $request->toArray(),
            'client' => $request->client?->toArray(),
            'meta' => ['changes' => $request->getChanges()],
        ], (int) $request->client_id);

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
            app(AutomationEngine::class)->run('request.completed', [
                'request' => $request->toArray(),
                'client' => $request->client?->toArray(),
            ], (int) $request->client_id);

            app(WebhookService::class)->triggerWebhook('request.completed', [
                'id' => $request->id,
                'client_id' => $request->client_id,
                'title' => $request->title,
                'completed_at' => optional($request->completed_at)->toISOString(),
            ], (int) $request->client_id);
        }
    }
}


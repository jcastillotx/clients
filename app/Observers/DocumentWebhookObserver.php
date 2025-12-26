<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class DocumentWebhookObserver
{
    public function __construct(
        protected WebhookService $webhooks,
        protected AutomationEngine $automations,
    ) {}

    public function created(Document $doc): void
    {
        $payload = [
            'event' => 'document.uploaded',
            'client_id' => $doc->client_id,
            'document' => [
                'id' => $doc->id,
                'client_id' => $doc->client_id,
                'title' => $doc->title,
                'category' => $doc->category,
                'request_id' => $doc->request_id,
                'is_public' => (bool) $doc->is_public,
            ],
        ];
        $this->webhooks->triggerWebhook('document.uploaded', [
            'id' => $doc->id,
            'client_id' => $doc->client_id,
            'title' => $doc->title,
            'category' => $doc->category,
            'request_id' => $doc->request_id,
        ], (int) $doc->client_id);
        $this->automations->trigger('document.uploaded', $payload);
    }

    public function updated(Document $doc): void
    {
        if ($doc->wasChanged('is_public') && $doc->is_public) {
            $payload = [
                'event' => 'document.shared',
                'client_id' => $doc->client_id,
                'document' => [
                    'id' => $doc->id,
                    'client_id' => $doc->client_id,
                    'title' => $doc->title,
                    'category' => $doc->category,
                    'request_id' => $doc->request_id,
                    'is_public' => (bool) $doc->is_public,
                ],
            ];
            $this->webhooks->triggerWebhook('document.shared', [
                'id' => $doc->id,
                'client_id' => $doc->client_id,
                'title' => $doc->title,
            ], (int) $doc->client_id);
            $this->automations->trigger('document.shared', $payload);
        }
    }
}


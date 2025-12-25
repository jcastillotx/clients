<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\WebhookService;

class DocumentWebhookObserver
{
    public function __construct(protected WebhookService $webhooks) {}

    public function created(Document $doc): void
    {
        $this->webhooks->triggerWebhook('document.uploaded', [
            'id' => $doc->id,
            'client_id' => $doc->client_id,
            'title' => $doc->title,
            'category' => $doc->category,
            'request_id' => $doc->request_id,
        ], (int) $doc->client_id);
    }

    public function updated(Document $doc): void
    {
        if ($doc->wasChanged('is_public') && $doc->is_public) {
            $this->webhooks->triggerWebhook('document.shared', [
                'id' => $doc->id,
                'client_id' => $doc->client_id,
                'title' => $doc->title,
            ], (int) $doc->client_id);
        }
    }
}


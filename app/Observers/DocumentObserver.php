<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\WebhookService;

class DocumentObserver
{
    public function created(Document $document): void
    {
        app(WebhookService::class)->triggerWebhook('document.uploaded', [
            'id' => $document->id,
            'client_id' => $document->client_id,
            'request_id' => $document->request_id,
            'title' => $document->title,
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'file_size' => (int) $document->file_size,
            'category' => $document->category,
            'created_at' => optional($document->created_at)->toISOString(),
        ], (int) $document->client_id);
    }
}


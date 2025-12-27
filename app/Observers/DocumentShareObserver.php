<?php

namespace App\Observers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\StorageFile;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class DocumentShareObserver
{
    public function created(DocumentShare $share): void
    {
        $source = $share->source;
        if ($source instanceof Document) {
            app(AutomationEngine::class)->run('document.shared', [
                'document' => $source->toArray(),
                'client' => $source->client?->toArray(),
                'meta' => ['share_token' => $share->token, 'expires_at' => optional($share->expires_at)->toISOString()],
            ], (int) $source->client_id);

            app(WebhookService::class)->triggerWebhook('document.shared', [
                'id' => $source->id,
                'client_id' => $source->client_id,
                'title' => $source->title,
                'share_token' => $share->token,
                'expires_at' => optional($share->expires_at)->toISOString(),
            ], (int) $source->client_id);
        } elseif ($source instanceof StorageFile) {
            $source->loadMissing('connection');
            $clientId = (int) ($source->connection?->client_id ?? 0);
            if ($clientId) {
                app(AutomationEngine::class)->run('document.shared', [
                    'storage' => [
                        'storage_file_id' => $source->id,
                        'filename' => $source->filename,
                        'provider' => $source->connection?->provider,
                    ],
                    'client' => $source->connection?->client?->toArray(),
                    'meta' => ['share_token' => $share->token, 'expires_at' => optional($share->expires_at)->toISOString()],
                ], $clientId);

                app(WebhookService::class)->triggerWebhook('document.shared', [
                    'storage_file_id' => $source->id,
                    'client_id' => $clientId,
                    'filename' => $source->filename,
                    'provider' => $source->connection?->provider,
                    'share_token' => $share->token,
                    'expires_at' => optional($share->expires_at)->toISOString(),
                ], $clientId);
            }
        }
    }
}

<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class ContractObserver
{
    public function created(Contract $contract): void
    {
        app(AutomationEngine::class)->run('contract.created', [
            'contract' => $contract->toArray(),
            'client' => $contract->client?->toArray(),
        ], (int) $contract->client_id);

        app(WebhookService::class)->triggerWebhook('contract.created', [
            'id' => $contract->id,
            'client_id' => $contract->client_id,
            'contract_number' => $contract->contract_number,
            'title' => $contract->title,
            'status' => $contract->status,
            'start_date' => optional($contract->start_date)->toDateString(),
            'end_date' => optional($contract->end_date)->toDateString(),
            'created_at' => optional($contract->created_at)->toISOString(),
        ], (int) $contract->client_id);
    }

    public function updated(Contract $contract): void
    {
        if (($contract->wasChanged('signed_at') || $contract->wasChanged('status')) && $contract->signed_at) {
            app(AutomationEngine::class)->run('contract.signed', [
                'contract' => $contract->toArray(),
                'client' => $contract->client?->toArray(),
            ], (int) $contract->client_id);

            app(WebhookService::class)->triggerWebhook('contract.signed', [
                'id' => $contract->id,
                'client_id' => $contract->client_id,
                'contract_number' => $contract->contract_number,
                'title' => $contract->title,
                'status' => $contract->status,
                'signed_at' => optional($contract->signed_at)->toISOString(),
            ], (int) $contract->client_id);
        }
    }
}

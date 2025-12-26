<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class ContractWebhookObserver
{
    public function __construct(
        protected WebhookService $webhooks,
        protected AutomationEngine $automations,
    ) {}

    public function created(Contract $contract): void
    {
        $payload = [
            'event' => 'contract.created',
            'client_id' => $contract->client_id,
            'contract' => [
                'id' => $contract->id,
                'client_id' => $contract->client_id,
                'title' => $contract->title,
                'status' => $contract->status,
                'start_date' => $contract->start_date?->toDateString(),
                'end_date' => $contract->end_date?->toDateString(),
            ],
        ];
        $this->webhooks->triggerWebhook('contract.created', [
            'id' => $contract->id,
            'client_id' => $contract->client_id,
            'title' => $contract->title,
            'status' => $contract->status,
            'start_date' => $contract->start_date?->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
        ], (int) $contract->client_id);
        $this->automations->trigger('contract.created', $payload);
    }

    public function updated(Contract $contract): void
    {
        if (($contract->wasChanged('signed_at') && $contract->signed_at) || ($contract->wasChanged('status') && $contract->status === 'active')) {
            $payload = [
                'event' => 'contract.signed',
                'client_id' => $contract->client_id,
                'contract' => [
                    'id' => $contract->id,
                    'client_id' => $contract->client_id,
                    'title' => $contract->title,
                    'status' => $contract->status,
                    'signed_at' => $contract->signed_at?->toISOString(),
                    'signed_by' => $contract->signed_by,
                    'end_date' => $contract->end_date?->toDateString(),
                ],
            ];
            $this->webhooks->triggerWebhook('contract.signed', [
                'id' => $contract->id,
                'client_id' => $contract->client_id,
                'title' => $contract->title,
                'signed_at' => $contract->signed_at?->toISOString(),
                'signed_by' => $contract->signed_by,
            ], (int) $contract->client_id);
            $this->automations->trigger('contract.signed', $payload);
        }
    }
}


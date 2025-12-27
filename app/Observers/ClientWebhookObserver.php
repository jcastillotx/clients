<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\AutomationEngine;

class ClientWebhookObserver
{
    public function __construct(protected AutomationEngine $automations) {}

    public function created(Client $client): void
    {
        $this->automations->trigger('client.created', [
            'event' => 'client.created',
            'client_id' => $client->id,
            'client' => [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'tier' => $client->tier,
                'status' => $client->status,
            ],
        ]);
    }

    public function updated(Client $client): void
    {
        if ($client->wasChanged('tier')) {
            $this->automations->trigger('client.tier_changed', [
                'event' => 'client.tier_changed',
                'client_id' => $client->id,
                'client' => [
                    'id' => $client->id,
                    'company_name' => $client->company_name,
                    'tier' => $client->tier,
                    'old_tier' => $client->getOriginal('tier'),
                ],
            ]);
        }
    }
}

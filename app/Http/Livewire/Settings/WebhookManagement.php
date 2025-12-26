<?php

namespace App\Http\Livewire\Settings;

use App\Models\Client;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookManagement extends Component
{
    use WithPagination;

    public ?int $clientId = null;
    public string $eventType = 'request.created';
    public string $webhookUrl = '';
    public string $secret = '';
    public bool $isActive = true;

    public ?int $selectedEndpointId = null;

    public array $supportedEvents = [
        'request.created',
        'request.updated',
        'request.completed',
        'invoice.created',
        'invoice.sent',
        'invoice.paid',
        'contract.created',
        'contract.expiring',
        'contract.signed',
        'document.uploaded',
        'document.shared',
        'payment.received',
        'payment.failed',
    ];

    protected function rules(): array
    {
        return [
            'clientId' => ['required', 'integer', 'exists:clients,id'],
            'eventType' => ['required', 'string', Rule::in($this->supportedEvents)],
            'webhookUrl' => ['required', 'url', 'max:2048'],
            'secret' => ['nullable', 'string', 'max:255'],
            'isActive' => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->clientId = $this->clientId ?: (int) (Client::query()->orderBy('id')->value('id') ?? 0);
        if ($this->clientId === 0) {
            $this->clientId = null;
        }
    }

    public function selectEndpoint(int $endpointId): void
    {
        $this->selectedEndpointId = $endpointId;
    }

    public function createWebhook(): void
    {
        $data = $this->validate();

        WebhookEndpoint::create([
            'client_id' => (int) $data['clientId'],
            'event_type' => $data['eventType'],
            'webhook_url' => $data['webhookUrl'],
            'secret' => $data['secret'] ?: null,
            'is_active' => (bool) $data['isActive'],
        ]);

        $this->reset(['webhookUrl', 'secret']);
        session()->flash('success', 'Webhook endpoint created.');
    }

    public function toggleWebhook(int $endpointId): void
    {
        $ep = WebhookEndpoint::query()->findOrFail($endpointId);
        $ep->update(['is_active' => ! $ep->is_active]);
    }

    public function deleteWebhook(int $endpointId): void
    {
        WebhookEndpoint::query()->whereKey($endpointId)->delete();
        if ($this->selectedEndpointId === $endpointId) {
            $this->selectedEndpointId = null;
        }
    }

    public function testWebhook(int $endpointId, WebhookService $webhooks): void
    {
        $delivery = $webhooks->testWebhook($endpointId);
        if ($delivery) {
            $this->selectedEndpointId = $delivery->webhook_endpoint_id;
        }
    }

    public function render()
    {
        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);

        $endpoints = WebhookEndpoint::query()
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->orderByDesc('id')
            ->paginate(10);

        $deliveries = collect();
        if ($this->selectedEndpointId) {
            $deliveries = WebhookDelivery::query()
                ->where('webhook_endpoint_id', $this->selectedEndpointId)
                ->orderByDesc('id')
                ->limit(50)
                ->get();
        }

        return view('livewire.settings.webhooks', [
            'clients' => $clients,
            'endpoints' => $endpoints,
            'deliveries' => $deliveries,
        ])->layout('layouts.admin', ['title' => 'Webhooks']);
    }
}


<?php

namespace App\Http\Livewire\Settings;

use App\Jobs\DeliverWebhookJob;
use App\Models\Client;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class WebhookManagement extends Component
{
    public ?int $client_id = null;
    public string $event_type = 'request.created';
    public string $webhook_url = '';
    public string $secret = '';
    public bool $is_active = true;
    public string $format = 'generic';
    public string $headers_json = '';

    public ?int $selectedEndpointId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('manage settings') || Auth::user()?->can('access admin panel'), 403);
    }

    public function createEndpoint(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = Validator::make([
            'client_id' => $this->client_id,
            'event_type' => $this->event_type,
            'webhook_url' => $this->webhook_url,
            'secret' => $this->secret,
            'is_active' => $this->is_active,
            'format' => $this->format,
            'headers_json' => $this->headers_json,
        ], [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'event_type' => ['required', 'string', 'max:100'],
            'webhook_url' => ['required', 'url', 'max:2048'],
            'secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'format' => ['required', 'in:generic,slack,teams,zapier,make'],
            'headers_json' => ['nullable', 'string'],
        ])->validate();

        $headers = null;
        if (trim((string) $data['headers_json']) !== '') {
            $decoded = json_decode((string) $data['headers_json'], true);
            abort_unless(is_array($decoded), 422, 'Headers must be valid JSON object.');
            $headers = $decoded;
        }

        $secret = trim((string) ($data['secret'] ?? ''));
        if ($secret === '') {
            $secret = Str::random(40);
        }

        WebhookEndpoint::create([
            'client_id' => $data['client_id'] ?? null,
            'event_type' => $data['event_type'],
            'webhook_url' => $data['webhook_url'],
            'secret' => $secret,
            'is_active' => (bool) $data['is_active'],
            'format' => $data['format'],
            'headers' => $headers,
            'created_by' => $user->id,
        ]);

        $this->reset(['webhook_url', 'secret', 'headers_json']);
        session()->flash('success', 'Webhook endpoint created.');
    }

    public function toggleActive(int $id): void
    {
        abort_unless(Auth::user(), 403);
        $ep = WebhookEndpoint::query()->findOrFail($id);
        $ep->update(['is_active' => !$ep->is_active]);
    }

    public function deleteEndpoint(int $id): void
    {
        abort_unless(Auth::user(), 403);
        $ep = WebhookEndpoint::query()->findOrFail($id);
        $ep->delete();
        if ($this->selectedEndpointId === $id) {
            $this->selectedEndpointId = null;
        }
        session()->flash('success', 'Webhook deleted.');
    }

    public function selectEndpoint(int $id): void
    {
        $this->selectedEndpointId = $id;
    }

    public function testEndpoint(int $id): void
    {
        abort_unless(Auth::user(), 403);
        $ep = WebhookEndpoint::query()->findOrFail($id);

        try {
            DeliverWebhookJob::dispatchSync($ep->id, $ep->event_type, [
                'test' => true,
                'message' => 'Test webhook delivery from Kre8iv Designs Client Portal.',
            ]);
            session()->flash('success', 'Test webhook sent (check logs below).');
        } catch (\Throwable $e) {
            session()->flash('error', 'Test webhook failed: ' . $e->getMessage());
        }
    }

    public function getEventOptionsProperty(): array
    {
        return [
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
    }

    public function render()
    {
        $endpoints = WebhookEndpoint::query()
            ->with('client')
            ->orderByDesc('id')
            ->get();

        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);

        $logs = collect();
        if ($this->selectedEndpointId) {
            $logs = WebhookDeliveryLog::query()
                ->where('webhook_endpoint_id', $this->selectedEndpointId)
                ->latest('id')
                ->limit(50)
                ->get();
        }

        return view('livewire.settings.webhooks', compact('endpoints', 'clients', 'logs'));
    }
}


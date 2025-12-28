<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AutomationEngine;
use App\Services\Entitlements\PortalEntitlementService;
use App\Services\WebhookService;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        app(AutomationEngine::class)->run('invoice.created', [
            'invoice' => $invoice->toArray(),
            'client' => $invoice->client?->toArray(),
        ], (int) $invoice->client_id);

        app(WebhookService::class)->triggerWebhook('invoice.created', [
            'id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'amount' => (float) $invoice->amount,
            'issue_date' => optional($invoice->issue_date)->toDateString(),
            'due_date' => optional($invoice->due_date)->toDateString(),
            'created_at' => optional($invoice->created_at)->toISOString(),
        ], (int) $invoice->client_id);
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status') && $invoice->status === 'sent') {
            app(AutomationEngine::class)->run('invoice.sent', [
                'invoice' => $invoice->toArray(),
                'client' => $invoice->client?->toArray(),
            ], (int) $invoice->client_id);

            app(WebhookService::class)->triggerWebhook('invoice.sent', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'amount' => (float) $invoice->amount,
            ], (int) $invoice->client_id);
        }

        if ($invoice->wasChanged('status') && $invoice->status === 'paid') {
            // If invoice items include a sold feature/service, enable it for the client and
            // sync portal user permissions accordingly.
            try {
                $client = $invoice->client;
                if ($client) {
                    $invoice->loadMissing('items');
                    $features = $invoice->items
                        ->pluck('feature_key')
                        ->filter(fn ($k) => is_string($k) && $k !== '')
                        ->unique()
                        ->values()
                        ->all();

                    foreach ($features as $feature) {
                        $client->enableFeature($feature);
                    }

                    app(PortalEntitlementService::class)->syncClientUsers($client);
                }
            } catch (\Throwable $e) {
                // Never break invoice status updates because of entitlements.
                \Log::warning('Entitlement sync failed for invoice '.$invoice->id.': '.$e->getMessage());
            }

            app(AutomationEngine::class)->run('invoice.paid', [
                'invoice' => $invoice->toArray(),
                'client' => $invoice->client?->toArray(),
            ], (int) $invoice->client_id);

            app(WebhookService::class)->triggerWebhook('invoice.paid', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'amount' => (float) $invoice->amount,
                'paid_at' => optional($invoice->paid_at)->toISOString(),
            ], (int) $invoice->client_id);
        }
    }
}

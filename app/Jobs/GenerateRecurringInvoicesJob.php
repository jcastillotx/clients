<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringInvoice;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notifications): void
    {
        $dueRecurring = RecurringInvoice::query()
            ->dueForGeneration()
            ->with('client')
            ->get();

        Log::info('Generating recurring invoices', ['count' => $dueRecurring->count()]);

        foreach ($dueRecurring as $recurring) {
            try {
                $this->generateInvoice($recurring, $notifications);
            } catch (\Throwable $e) {
                Log::error('Failed to generate recurring invoice', [
                    'recurring_invoice_id' => $recurring->id,
                    'client_id' => $recurring->client_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate a single invoice from a recurring template.
     */
    protected function generateInvoice(RecurringInvoice $recurring, NotificationService $notifications): Invoice
    {
        return DB::transaction(function () use ($recurring, $notifications) {
            $issueDate = now();
            $dueDate = $issueDate->copy()->addDays($recurring->payment_terms_days);

            // Create the invoice
            $invoice = Invoice::create([
                'client_id' => $recurring->client_id,
                'request_id' => $recurring->request_id,
                'contract_id' => $recurring->contract_id,
                'recurring_invoice_id' => $recurring->id,
                'invoice_number' => null, // Auto-generated
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'tax_rate' => $recurring->tax_rate,
                'discount' => $recurring->discount,
                'discount_type' => $recurring->discount_type ?? 'fixed',
                'notes' => $recurring->notes,
                'terms' => $recurring->terms,
                'template' => $recurring->template,
                'status' => $recurring->auto_send ? 'sent' : 'draft',
            ]);

            // Create line items
            foreach ($recurring->line_items as $idx => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'] ?? '',
                    'feature_key' => $item['feature_key'] ?? null,
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'total' => (float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0),
                    'sort_order' => $idx,
                ]);
            }

            // Calculate totals
            $invoice->refresh()->load('items');
            $invoice->calculateTotals();

            // Mark as sent if auto_send
            if ($recurring->auto_send) {
                $invoice->markAsSent();
            }

            // Advance the recurring invoice to next occurrence
            $recurring->advanceToNextOccurrence();

            // Log activity
            ActivityLog::log(
                "Generated recurring invoice: {$invoice->invoice_number} from template '{$recurring->name}'",
                $invoice,
                [
                    'recurring_invoice_id' => $recurring->id,
                    'client_id' => $recurring->client_id,
                    'auto_send' => $recurring->auto_send,
                ],
                'created',
                'invoices'
            );

            // Send notification if auto_send is enabled
            if ($recurring->auto_send) {
                $notifications->sendInvoiceNotification($invoice->fresh(['client']), 'created');
            }

            Log::info('Generated recurring invoice', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recurring_invoice_id' => $recurring->id,
                'next_generate_date' => $recurring->next_generate_date?->toDateString(),
            ]);

            return $invoice;
        });
    }
}

<?php

namespace App\Services\AI;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Carbon;

class AdminAssistantToolingService
{
    /**
     * Execute simple, safe “tools” for the admin assistant.
     *
     * @return array{handled:bool, answer:string, meta?:array<string,mixed>}
     */
    public function tryHandle(string $question, array $context = []): array
    {
        $q = strtolower(trim($question));

        // How many active clients?
        if (str_contains($q, 'how many') && str_contains($q, 'active') && str_contains($q, 'client')) {
            $count = Client::query()->where('status', 'active')->count();
            return ['handled' => true, 'answer' => "There are {$count} active clients."];
        }

        // Revenue last month
        if (str_contains($q, 'revenue') && str_contains($q, 'last month')) {
            $start = now()->subMonthNoOverflow()->startOfMonth();
            $end = now()->subMonthNoOverflow()->endOfMonth();
            $sum = (float) Payment::query()
                ->where('status', 'succeeded')
                ->whereBetween('processed_at', [$start, $end])
                ->sum('amount');
            return ['handled' => true, 'answer' => 'Revenue last month (successful payments) was $' . number_format($sum, 2) . '.'];
        }

        // Recommend follow-up: pick clients with highest unpaid invoices + no recent request.
        if (str_contains($q, 'which client') && (str_contains($q, 'follow up') || str_contains($q, 'follow-up'))) {
            $clients = Client::query()
                ->where('status', 'active')
                ->get(['id', 'company_name']);

            $best = null;
            foreach ($clients as $c) {
                $unpaid = (float) $c->unpaid_invoices_total;
                $lastReq = ServiceRequest::query()->where('client_id', $c->id)->max('created_at');
                $days = $lastReq ? Carbon::parse($lastReq)->diffInDays(now()) : 365;
                $score = ($unpaid > 0 ? min(1000, $unpaid) : 0) + ($days * 2);
                if (!$best || $score > $best['score']) {
                    $best = ['client' => $c, 'score' => $score, 'unpaid' => $unpaid, 'days' => $days];
                }
            }

            if (!$best) {
                return ['handled' => true, 'answer' => 'No active clients found to recommend.'];
            }

            /** @var Client $c */
            $c = $best['client'];
            return [
                'handled' => true,
                'answer' => "Recommended follow-up: {$c->company_name}. Unpaid invoices: $" . number_format((float) $best['unpaid'], 2) . ". Days since last request: {$best['days']}.",
                'meta' => ['client_id' => $c->id],
            ];
        }

        // Action: create invoice for Client X (very conservative: draft invoice with $0 item)
        if (str_starts_with($q, 'create invoice') || str_contains($q, 'create an invoice')) {
            // naive extraction: after "for"
            $clientName = null;
            if (preg_match('/invoice\s+for\s+(.+)$/i', $question, $m)) {
                $clientName = trim((string) $m[1]);
            }

            if (!$clientName && !empty($context['client_id'])) {
                $client = Client::query()->find((int) $context['client_id']);
            } else {
                $client = $clientName
                    ? Client::query()->where('company_name', 'like', '%' . $clientName . '%')->orderBy('company_name')->first()
                    : null;
            }

            if (!$client) {
                return ['handled' => true, 'answer' => 'I couldn’t find that client. Try “Create invoice for <company name>”.'];
            }

            $invoice = Invoice::create([
                'client_id' => $client->id,
                'request_id' => $context['request_id'] ?? null,
                'contract_id' => null,
                'invoice_number' => null,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'tax_rate' => 0,
                'discount' => 0,
                'notes' => 'Draft created by AI assistant (review and edit).',
                'terms' => null,
                'status' => 'draft',
                'template' => 'classic',
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Services (fill in)',
                'quantity' => 1,
                'unit_price' => 0,
                'total' => 0,
                'sort_order' => 0,
            ]);

            $invoice->refresh()->load('items');
            if (method_exists($invoice, 'calculateTotals')) {
                $invoice->calculateTotals();
            }

            return [
                'handled' => true,
                'answer' => "Draft invoice created for {$client->company_name}: Invoice #{$invoice->id}. You can review it at /invoices/{$invoice->id}.",
                'meta' => ['invoice_id' => $invoice->id, 'client_id' => $client->id],
            ];
        }

        return ['handled' => false, 'answer' => ''];
    }
}


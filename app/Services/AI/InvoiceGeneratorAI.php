<?php

namespace App\Services\AI;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Request as ServiceRequest;
use App\Models\RequestEstimate;
use App\Models\Setting;
use App\Services\AI\Prompts\InvoicePrompts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceGeneratorAI
{
    public function __construct(protected AISafetyService $safety)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function autoGenerateInvoice(ServiceRequest $request, array $options = []): array
    {
        $request->loadMissing(['client', 'timeEntries']);
        $client = $request->client;

        $catalog = $this->servicesCatalog($client);
        $rate = $this->hourlyRateForClient($client);
        $hours = $this->bestHours($request);

        $context = [
            'client' => [
                'id' => $client?->id,
                'name' => $client?->company_name,
                'tier' => $client?->tier,
                'industry' => $client?->industry,
            ],
            'rates' => [
                'default_hourly_rate' => $rate,
            ],
            'hours' => [
                'best_hours' => $hours,
                'estimated_hours' => (float) ($request->estimated_hours ?? 0),
                'actual_hours' => (float) ($request->actual_hours ?? 0),
            ],
        ];

        $messages = [
            ['role' => 'system', 'content' => InvoicePrompts::generateSystem()],
            ['role' => 'user', 'content' => InvoicePrompts::generateUser($request, $catalog, $context)],
        ];

        // Claude for analysis, with safe wrapper + fallback.
        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => 'claude',
                'task_type' => 'invoice_ai_generate',
                'timeout' => 180,
                'client_id' => $client?->id,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => $request->title,
                // used for financial review checks
                'estimated_project_cost_usd' => (float) ($request->estimated_cost ?? 0),
            ]);

            $data = $this->parseJson((string) ($res['text'] ?? '')) ?? null;
            if (is_array($data)) {
                $data['_meta'] = [
                    'quality_score' => $res['quality_score'] ?? null,
                    'review_queue_id' => $res['review_queue_id'] ?? null,
                ];
                return $this->normalizeGeneratedInvoice($data, $rate);
            }
        } catch (\Throwable) {
            // fall back
        }

        return $this->fallbackGeneratedInvoice($request, $rate, $hours);
    }

    /**
     * Invoice review assistant: math check, missing items, estimate variance, notes.
     *
     * @return array<string,mixed>
     */
    public function reviewInvoice(Invoice $invoice, array $options = []): array
    {
        $invoice->loadMissing(['items', 'client', 'request']);
        $client = $invoice->client;

        $estimateCtx = [];
        if ($invoice->request_id) {
            $est = RequestEstimate::query()
                ->where('request_id', $invoice->request_id)
                ->orderByDesc('id')
                ->first();
            if ($est) {
                $estimateCtx = [
                    'estimate_id' => $est->id,
                    'status' => $est->status,
                    'estimate_data' => $est->estimate_data,
                    'pricing_data' => $est->pricing_data,
                ];
            }
        }

        $math = $this->mathCheck($invoice);
        $messages = [
            ['role' => 'system', 'content' => InvoicePrompts::reviewSystem()],
            ['role' => 'user', 'content' => InvoicePrompts::reviewUser($invoice, $estimateCtx)],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => 'claude',
                'task_type' => 'invoice_ai_review',
                'timeout' => 180,
                'client_id' => $client?->id,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => 'Review invoice #' . $invoice->id,
                'estimated_project_cost_usd' => (float) $invoice->amount,
            ]);

            $data = $this->parseJson((string) ($res['text'] ?? ''));
            if (is_array($data)) {
                $data['_math_check'] = $math;
                $data['_meta'] = [
                    'quality_score' => $res['quality_score'] ?? null,
                    'review_queue_id' => $res['review_queue_id'] ?? null,
                ];
                return $data;
            }
        } catch (\Throwable) {
        }

        // Deterministic fallback
        return [
            'math_ok' => $math['ok'],
            'missing_items' => [],
            'variances' => $math['issues'],
            'suggested_notes_for_client' => $math['ok'] ? [] : ['Invoice totals were recalculated due to rounding differences.'],
            'suggested_internal_notes' => [],
            'flags' => $math['ok'] ? [] : ['math_mismatch'],
            '_meta' => ['fallback' => true],
        ];
    }

    /**
     * Pricing optimizer (GPT-4 for pricing calculations).
     *
     * @return array<string,mixed>
     */
    public function optimizePricing(Invoice $invoice, Client $client, array $options = []): array
    {
        $invoice->loadMissing('items');

        $history = $this->paymentHistoryStats($client);
        $messages = [
            ['role' => 'system', 'content' => InvoicePrompts::pricingSystem()],
            ['role' => 'user', 'content' => InvoicePrompts::pricingUser($invoice, $client, $history, $options['market'] ?? [])],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => 'openai',
                'task_type' => 'invoice_pricing_optimize',
                'timeout' => 180,
                'client_id' => $client->id,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => 'Optimize pricing',
                'estimated_project_cost_usd' => (float) $invoice->amount,
            ]);
            $data = $this->parseJson((string) ($res['text'] ?? ''));
            if (is_array($data)) {
                $data['_history'] = $history;
                $data['_meta'] = [
                    'quality_score' => $res['quality_score'] ?? null,
                    'review_queue_id' => $res['review_queue_id'] ?? null,
                ];
                return $data;
            }
        } catch (\Throwable) {
        }

        // Fallback heuristic: small loyalty discount for prompt payers
        $disc = 0.0;
        if (($history['avg_days_to_pay'] ?? 999) <= 7 && ($history['paid_invoice_count'] ?? 0) >= 5) {
            $disc = round(min(250, max(0, (float) $invoice->amount * 0.03)), 2);
        }

        return [
            'recommended_adjustments' => [],
            'suggested_discount' => $disc > 0 ? ['amount' => $disc, 'reason' => 'Loyalty discount for consistently prompt payment.'] : null,
            'bundle_recommendations' => [],
            'market_rate_notes' => [],
            'confidence' => 'low',
            '_history' => $history,
            '_meta' => ['fallback' => true],
        ];
    }

    /**
     * Predict payment date (deterministic, optional AI later).
     *
     * @return array<string,mixed>
     */
    public function predictPayment(Invoice $invoice, Client $client): array
    {
        $history = $this->paymentHistoryStats($client);
        $avg = (int) ($history['avg_days_to_pay'] ?? 14);
        $avg = max(1, min(60, $avg));

        $base = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : now();
        $pred = $base->copy()->addDays($avg);

        $confidence = ($history['paid_invoice_count'] ?? 0) >= 8 ? 'high' : (($history['paid_invoice_count'] ?? 0) >= 3 ? 'medium' : 'low');

        $reminders = [];
        if ($invoice->due_date) {
            $due = Carbon::parse($invoice->due_date);
            $reminders[] = ['when' => $due->copy()->subDays(7)->toDateString(), 'message' => 'Friendly reminder: invoice due in 7 days.'];
            $reminders[] = ['when' => $due->copy()->addDays(3)->toDateString(), 'message' => 'Overdue reminder: invoice is past due.'];
        }

        return [
            'predicted_payment_date' => $pred->toDateString(),
            'confidence' => $confidence,
            'reasons' => [
                'Based on historical average days-to-pay for this client.',
            ],
            'recommended_reminders' => $reminders,
            '_history' => $history,
        ];
    }

    /**
     * Dispute resolution assistant.
     *
     * @return array<string,mixed>
     */
    public function disputeResolution(Invoice $invoice, Client $client, string $disputeText, array $options = []): array
    {
        $invoice->loadMissing('items');
        $payload = [
            'invoice' => [
                'id' => $invoice->id,
                'amount' => (float) $invoice->amount,
                'items' => $invoice->items->map(fn ($it) => [
                    'description' => $it->description,
                    'quantity' => (float) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'total' => (float) $it->total,
                ])->values()->all(),
            ],
            'client' => [
                'id' => $client->id,
                'name' => $client->company_name,
                'tier' => $client->tier,
            ],
            'dispute' => $disputeText,
        ];

        $messages = [
            ['role' => 'system', 'content' => InvoicePrompts::disputeSystem()],
            ['role' => 'user', 'content' => "Analyze this invoice dispute and draft a diplomatic response.\n\nJSON:\n" . json_encode($payload, JSON_UNESCAPED_SLASHES)],
        ];

        try {
            $res = $this->safety->safeChat($messages, [
                'provider' => 'claude',
                'task_type' => 'invoice_dispute_resolution',
                'timeout' => 180,
                'client_id' => $client->id,
                'user_id' => $options['user_id'] ?? null,
                'user_query' => 'Dispute resolution',
                'estimated_project_cost_usd' => (float) $invoice->amount,
            ]);
            $data = $this->parseJson((string) ($res['text'] ?? ''));
            return is_array($data) ? $data : ['error' => (string) ($res['text'] ?? 'Unable to analyze.')];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Suggest payment plan for large invoices.
     *
     * @return array<string,mixed>
     */
    public function suggestPaymentPlan(Invoice $invoice, Client $client, array $options = []): array
    {
        $amount = (float) $invoice->amount;
        $threshold = (float) (Setting::getValue('ai.safety.financial_review_threshold_usd', 5000) ?? 5000);

        // Simple deterministic plan
        if ($amount < max(1000, $threshold)) {
            return [
                'plan' => [],
                'rationale' => 'Invoice amount does not typically require a payment plan.',
                'client_friendly_message' => '',
                '_meta' => ['fallback' => true],
            ];
        }

        $count = $amount >= 15000 ? 4 : 3;
        $each = round($amount / $count, 2);
        $start = $invoice->due_date ? Carbon::parse($invoice->due_date)->copy() : now()->addDays(7);

        $plan = [];
        for ($i = 0; $i < $count; $i++) {
            $plan[] = [
                'due_date' => $start->copy()->addDays($i * 14)->toDateString(),
                'amount' => $i === $count - 1 ? round($amount - ($each * ($count - 1)), 2) : $each,
                'notes' => $i === 0 ? 'Initial installment' : 'Installment',
            ];
        }

        return [
            'plan' => $plan,
            'rationale' => 'Split into installments to reduce late-payment risk while maintaining cash flow.',
            'client_friendly_message' => 'We can offer a split payment plan to make this easier to process. Let us know if you’d like to proceed.',
            '_meta' => ['fallback' => true],
        ];
    }

    /**
     * Apply generated items to an invoice (replace existing).
     *
     * @param array<int,array{description:string,quantity:float,unit_price:float}> $items
     */
    public function applyItemsToInvoice(Invoice $invoice, array $items, float $discount = 0.0, float $taxRate = 0.0, ?string $notes = null): void
    {
        DB::transaction(function () use ($invoice, $items, $discount, $taxRate, $notes) {
            $invoice->loadMissing('items');
            InvoiceItem::query()->where('invoice_id', $invoice->id)->delete();

            foreach (array_values($items) as $idx => $it) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => (string) $it['description'],
                    'quantity' => (float) $it['quantity'],
                    'unit_price' => (float) $it['unit_price'],
                    'total' => (float) ((float) $it['quantity'] * (float) $it['unit_price']),
                    'sort_order' => $idx,
                ]);
            }

            $invoice->update([
                'discount' => max(0, $discount),
                'tax_rate' => max(0, $taxRate),
                'notes' => $notes !== null && trim($notes) !== '' ? $notes : $invoice->notes,
            ]);

            $invoice->refresh()->load('items');
            if (method_exists($invoice, 'calculateTotals')) {
                $invoice->calculateTotals();
            }
        });
    }

    // -------------------------
    // Helpers
    // -------------------------

    protected function parseJson(string $text): ?array
    {
        $t = trim($text);
        if ($t === '') return null;
        $decoded = json_decode($t, true);
        if (is_array($decoded)) return $decoded;
        $start = strpos($t, '{');
        $end = strrpos($t, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($t, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) return $decoded;
        }
        return null;
    }

    protected function normalizeGeneratedInvoice(array $data, float $defaultRate): array
    {
        $items = $data['items'] ?? [];
        $norm = [];
        foreach (is_array($items) ? $items : [] as $row) {
            if (!is_array($row)) continue;
            $desc = trim((string) ($row['description'] ?? ''));
            $qty = (float) ($row['quantity'] ?? 0);
            $unit = (float) ($row['unit_price'] ?? 0);
            if ($desc === '' || $qty <= 0) continue;
            if ($unit <= 0) $unit = $defaultRate;
            $norm[] = [
                'description' => $desc,
                'quantity' => round($qty, 2),
                'unit_price' => round($unit, 2),
                'accounting_category' => $row['accounting_category'] ?? null,
                'tax_category' => $row['tax_category'] ?? null,
                'confidence' => $row['confidence'] ?? 'medium',
            ];
        }
        if (empty($norm)) {
            $norm = [[
                'description' => 'Professional services',
                'quantity' => 1,
                'unit_price' => round($defaultRate, 2),
                'accounting_category' => null,
                'tax_category' => null,
                'confidence' => 'low',
            ]];
        }

        $data['items'] = $norm;
        $data['discount'] = (float) ($data['discount'] ?? 0);
        $data['tax_rate'] = (float) ($data['tax_rate'] ?? 0);
        return $data;
    }

    protected function fallbackGeneratedInvoice(ServiceRequest $request, float $rate, float $hours): array
    {
        $qty = $hours > 0 ? $hours : 1;
        return [
            'currency' => 'USD',
            'items' => [[
                'description' => 'Services: ' . $request->title,
                'quantity' => round($qty, 2),
                'unit_price' => round($rate, 2),
                'accounting_category' => 'Services',
                'tax_category' => null,
                'confidence' => 'low',
            ]],
            'discount' => 0,
            'tax_rate' => (float) config('client-portal.invoice.tax_rate', 0),
            'notes_for_client' => 'Thank you for your business.',
            'internal_notes' => 'Auto-generated fallback invoice items. Review before sending.',
            'assumptions' => ['Billed as time-and-materials at default hourly rate.'],
            'missing_items' => [],
            'risks' => [],
            '_meta' => ['fallback' => true],
        ];
    }

    protected function servicesCatalog(?Client $client): array
    {
        $catalog = Setting::getValue('billing.services_catalog', null);
        if (is_array($catalog) && !empty($catalog)) return $catalog;

        $rate = $this->hourlyRateForClient($client);
        return [
            ['name' => 'Web development (hourly)', 'unit' => 'hour', 'default_rate' => $rate, 'accounting_category' => 'Services', 'tax_category' => null],
            ['name' => 'Design (hourly)', 'unit' => 'hour', 'default_rate' => $rate, 'accounting_category' => 'Services', 'tax_category' => null],
            ['name' => 'Project management', 'unit' => 'hour', 'default_rate' => $rate, 'accounting_category' => 'Services', 'tax_category' => null],
            ['name' => 'Maintenance / support', 'unit' => 'hour', 'default_rate' => $rate, 'accounting_category' => 'Services', 'tax_category' => null],
        ];
    }

    protected function hourlyRateForClient(?Client $client): float
    {
        $base = (float) (Setting::getValue('billing.hourly_rate', 100) ?? 100);
        if (!$client) return $base;
        $tier = strtolower((string) ($client->tier ?? ''));
        $multipliers = (array) (Setting::getValue('billing.tier_rate_multipliers', [
            'basic' => 1.0,
            'standard' => 1.0,
            'premium' => 1.15,
            'enterprise' => 1.25,
        ]) ?? []);
        $m = isset($multipliers[$tier]) ? (float) $multipliers[$tier] : 1.0;
        return round($base * $m, 2);
    }

    protected function bestHours(ServiceRequest $request): float
    {
        $actual = (float) ($request->actual_hours ?? 0);
        if ($actual > 0) return $actual;
        $estimated = (float) ($request->estimated_hours ?? 0);
        if ($estimated > 0) return $estimated;
        if ($request->relationLoaded('timeEntries')) {
            $sum = (float) $request->timeEntries->sum('hours');
            if ($sum > 0) return $sum;
        }
        return 0.0;
    }

    /**
     * @return array{ok:bool, issues:array<int,array<string,string>>}
     */
    protected function mathCheck(Invoice $invoice): array
    {
        $sumItems = (float) $invoice->items->sum('total');
        $taxRate = (float) ($invoice->tax_rate ?? 0);
        $tax = round($sumItems * ($taxRate / 100), 2);
        $discount = (float) ($invoice->discount ?? 0);
        $calcTotal = round(max(0, $sumItems + $tax - $discount), 2);

        $issues = [];
        if (abs($calcTotal - (float) $invoice->amount) > 0.02) {
            $issues[] = [
                'label' => 'Total mismatch',
                'expected' => '$' . number_format($calcTotal, 2),
                'actual' => '$' . number_format((float) $invoice->amount, 2),
                'severity' => 'high',
            ];
        }

        return ['ok' => empty($issues), 'issues' => $issues];
    }

    /**
     * @return array<string,mixed>
     */
    protected function paymentHistoryStats(Client $client): array
    {
        $invoices = $client->invoices()->whereNotNull('paid_at')->limit(50)->get(['issue_date', 'paid_at', 'amount']);
        $days = [];
        foreach ($invoices as $inv) {
            if ($inv->issue_date && $inv->paid_at) {
                $days[] = Carbon::parse($inv->issue_date)->diffInDays(Carbon::parse($inv->paid_at));
            }
        }
        $avg = !empty($days) ? (int) round(array_sum($days) / count($days)) : null;

        return [
            'paid_invoice_count' => count($days),
            'avg_days_to_pay' => $avg,
        ];
    }
}


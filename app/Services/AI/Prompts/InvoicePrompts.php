<?php

namespace App\Services\AI\Prompts;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Request as ServiceRequest;

class InvoicePrompts
{
    public static function generateSystem(): string
    {
        return <<<'SYS'
You are an invoicing assistant for a digital agency.
Return ONLY valid JSON, no markdown.

Schema:
{
  "currency":"USD",
  "items":[
    {
      "description":"string",
      "quantity": number,
      "unit_price": number,
      "accounting_category":"string|null",
      "tax_category":"string|null",
      "confidence":"low|medium|high"
    }
  ],
  "discount": number,
  "tax_rate": number,
  "notes_for_client":"string",
  "internal_notes":"string",
  "assumptions":["string",...],
  "missing_items":["string",...],
  "risks":["string",...]
}

Rules:
- Use conservative pricing unless explicit rates are provided in the context.
- If you cannot infer quantities, pick sensible defaults and include assumptions.
- Never include secrets or personal data in output.
SYS;
    }

    public static function generateUser(ServiceRequest $request, array $catalog, array $context = []): string
    {
        $payload = [
            'request' => [
                'id' => $request->id,
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'estimated_hours' => (float) ($request->estimated_hours ?? 0),
                'actual_hours' => (float) ($request->actual_hours ?? 0),
                'estimated_cost' => (float) ($request->estimated_cost ?? 0),
                'status' => $request->status,
            ],
            'services_catalog' => $catalog,
            'context' => $context,
        ];

        return "Generate invoice line items for this completed request.\n\nJSON context:\n".
            json_encode($payload, JSON_UNESCAPED_SLASHES).
            "\n\nReturn JSON in the schema.";
    }

    public static function reviewSystem(): string
    {
        return <<<'SYS'
You are an invoice review assistant. Return ONLY valid JSON, no markdown.

Schema:
{
  "math_ok": true|false,
  "missing_items":["string",...],
  "variances":[{"label":"string","expected":"string","actual":"string","severity":"low|medium|high"}],
  "suggested_notes_for_client":["string",...],
  "suggested_internal_notes":["string",...],
  "flags":["string",...]
}

Rules:
- If you cite a fact, ensure it is supported by provided context.
- Flag large overages and suggest a clear explanation.
SYS;
    }

    public static function reviewUser(Invoice $invoice, array $estimateContext = []): string
    {
        $payload = [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'issue_date' => optional($invoice->issue_date)->toDateString(),
                'due_date' => optional($invoice->due_date)->toDateString(),
                'subtotal' => (float) ($invoice->subtotal ?? 0),
                'tax_rate' => (float) ($invoice->tax_rate ?? 0),
                'tax_amount' => (float) ($invoice->tax_amount ?? 0),
                'discount' => (float) ($invoice->discount ?? 0),
                'amount' => (float) ($invoice->amount ?? 0),
                'items' => $invoice->items->map(fn ($it) => [
                    'description' => $it->description,
                    'quantity' => (float) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'total' => (float) $it->total,
                ])->values()->all(),
            ],
            'estimate' => $estimateContext,
        ];

        return "Review this invoice for math/missing items and compare to estimate.\n\nJSON context:\n".
            json_encode($payload, JSON_UNESCAPED_SLASHES).
            "\n\nReturn JSON in the schema.";
    }

    public static function pricingSystem(): string
    {
        return <<<'SYS'
You are a pricing strategist for a digital agency. Return ONLY valid JSON.

Schema:
{
  "recommended_adjustments":[{"scope":"invoice|item","target":"string","change":"string","reason":"string"}],
  "suggested_discount":{"amount":number,"reason":"string"}|null,
  "bundle_recommendations":["string",...],
  "market_rate_notes":["string",...],
  "confidence":"low|medium|high"
}
SYS;
    }

    public static function pricingUser(Invoice $invoice, Client $client, array $history, array $market = []): string
    {
        $payload = [
            'client' => [
                'id' => $client->id,
                'name' => $client->company_name,
                'tier' => $client->tier,
                'industry' => $client->industry,
            ],
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
            'payment_history' => $history,
            'market' => $market,
        ];

        return "Optimize pricing for this invoice based on client tier/history and market notes.\n\nJSON context:\n".
            json_encode($payload, JSON_UNESCAPED_SLASHES).
            "\n\nReturn JSON in the schema.";
    }

    public static function paymentPredictionSystem(): string
    {
        return <<<'SYS'
You are a finance operations assistant. Return ONLY valid JSON.

Schema:
{
  "predicted_payment_date":"YYYY-MM-DD",
  "confidence":"low|medium|high",
  "reasons":["string",...],
  "recommended_reminders":[{"when":"YYYY-MM-DD","message":"string"}]
}
SYS;
    }

    public static function disputeSystem(): string
    {
        return <<<'SYS'
You are a diplomatic billing support agent. Return ONLY valid JSON.

Schema:
{
  "summary":"string",
  "likely_root_cause":"string",
  "recommended_resolution":["string",...],
  "draft_response_email":{"subject":"string","body":"string"},
  "confidence":"low|medium|high"
}
SYS;
    }

    public static function paymentPlanSystem(): string
    {
        return <<<'SYS'
You are a billing strategist. Return ONLY valid JSON.

Schema:
{
  "plan":[{"due_date":"YYYY-MM-DD","amount":number,"notes":"string"}],
  "rationale":"string",
  "client_friendly_message":"string"
}
SYS;
    }
}

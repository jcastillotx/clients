<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Invoice */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'request_id' => $this->request_id,
            'invoice_number' => $this->invoice_number,
            'subtotal' => (float) $this->subtotal,
            'tax_rate' => (float) $this->tax_rate,
            'tax_amount' => (float) $this->tax_amount,
            'discount' => (float) $this->discount,
            'amount' => (float) $this->amount,
            'issue_date' => optional($this->issue_date)->toDateString(),
            'due_date' => optional($this->due_date)->toDateString(),
            'paid_at' => optional($this->paid_at)->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'client' => $this->whenLoaded('client', fn () => new ClientResource($this->client)),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($it) => [
                    'id' => $it->id,
                    'description' => $it->description,
                    'quantity' => (float) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'total' => (float) $it->total,
                    'sort_order' => (int) $it->sort_order,
                ]);
            }),
        ];
    }
}


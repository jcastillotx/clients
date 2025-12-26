<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user?->currentAccessToken()?->can('admin') ?? false;

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'request_id' => ['nullable', 'integer', 'exists:requests,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (!$isAdmin && $user?->client_id) {
            abort_unless((int) $data['client_id'] === (int) $user->client_id, 403);
        }

        $invoice = Invoice::create([
            'client_id' => $data['client_id'],
            'request_id' => $data['request_id'] ?? null,
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'status' => $data['status'] ?? 'draft',
            'tax_rate' => (float) ($data['tax_rate'] ?? 0),
            'discount' => (float) ($data['discount'] ?? 0),
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
        ]);

        $items = (array) ($data['items'] ?? []);
        $sort = 1;
        foreach ($items as $it) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => (string) ($it['description'] ?? ''),
                'quantity' => (float) ($it['quantity'] ?? 1),
                'unit_price' => (float) ($it['unit_price'] ?? 0),
                'sort_order' => $sort++,
            ]);
        }

        // Ensure totals are computed.
        $invoice->refresh();
        $invoice->calculateTotals();
        $invoice->refresh();

        return response()->json(['data' => $invoice], 201);
    }

    public function show(string $id)
    {
        $invoice = Invoice::query()->with(['client', 'items', 'payments'])->findOrFail($id);
        $user = request()->user();
        $isAdmin = $user?->currentAccessToken()?->can('admin') ?? false;
        if (!$isAdmin && $user?->client_id) {
            abort_unless((int) $invoice->client_id === (int) $user->client_id, 403);
        }
        return response()->json(['data' => $invoice]);
    }
}


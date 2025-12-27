<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = Validator::make($request->all(), [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'request_id' => ['nullable', 'integer', 'exists:requests,id'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,sent,paid,overdue,cancelled,refunded'],
        ])->validate();

        if ($user->isClient()) {
            $data['client_id'] = $user->client_id;
        } else {
            $data['client_id'] = $data['client_id'] ?? null;
            abort_unless($data['client_id'], 422, 'client_id is required for staff invoices.');
        }

        $invoice = DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'client_id' => $data['client_id'],
                'request_id' => $data['request_id'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $items = $data['items'] ?? [];
            $sort = 0;
            foreach ($items as $it) {
                $qty = isset($it['quantity']) ? (float) $it['quantity'] : 1;
                $unit = isset($it['unit_price']) ? (float) $it['unit_price'] : 0;
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => (string) $it['description'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'total' => $qty * $unit,
                    'sort_order' => $sort++,
                ]);
            }

            $invoice->calculateTotals();

            return $invoice;
        });

        return response()->json([
            'data' => new InvoiceResource($invoice->load(['client', 'items'])),
        ], 201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->isClient()) {
            abort_unless((int) $invoice->client_id === (int) $user->client_id, 403);
        }

        return response()->json([
            'data' => new InvoiceResource($invoice->load(['client', 'items'])),
        ]);
    }
}

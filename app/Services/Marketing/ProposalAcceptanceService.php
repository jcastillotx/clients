<?php

namespace App\Services\Marketing;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Proposal;
use App\Models\ProposalSelection;
use Illuminate\Support\Facades\DB;

class ProposalAcceptanceService
{
    /**
     * Accept (sign) a proposal and create a contract + invoice (best-effort).
     *
     * @return array{contract:Contract|null, invoice:Invoice|null}
     */
    public function accept(Proposal $proposal, array $signature): array
    {
        return DB::transaction(function () use ($proposal, $signature) {
            $proposal->refresh();
            $proposal->loadMissing(['client', 'request']);

            $selection = ProposalSelection::query()
                ->where('proposal_id', $proposal->id)
                ->orderByDesc('id')
                ->first();

            $pricing = (array) ($proposal->pricing_data ?? []);
            $tiers = (array) ($pricing['tiers'] ?? []);

            $tierKey = (string) ($selection?->selected_tier ?? 'better');
            $tier = is_array($tiers[$tierKey] ?? null) ? $tiers[$tierKey] : [];
            $tierLabel = (string) (($tier['label'] ?? null) ?: ucfirst($tierKey));
            $tierAmount = (float) (($tier['amount'] ?? null) ?: 0);

            $contract = Contract::create([
                'client_id' => $proposal->client_id,
                'title' => 'Contract — ' . ($proposal->title ?: 'Proposal'),
                'description' => $proposal->request ? ('Linked to Request #' . $proposal->request->id) : null,
                'value' => $selection?->total_amount ?? $tierAmount,
                'status' => 'active',
                'signed_at' => now(),
                'signed_by' => (string) ($signature['name'] ?? ''),
                'signature_ip' => (string) ($signature['ip'] ?? ''),
                'signature_data' => (string) ($signature['data'] ?? ''),
                'meta' => [
                    'source' => 'proposal',
                    'proposal_id' => $proposal->id,
                    'proposal_number' => $proposal->proposal_number,
                ],
            ]);

            $invoice = Invoice::create([
                'client_id' => $proposal->client_id,
                'request_id' => $proposal->request_id,
                'contract_id' => $contract->id,
                'status' => 'draft',
                'notes' => 'Generated from accepted proposal ' . $proposal->proposal_number,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Proposal tier: ' . $tierLabel,
                'quantity' => 1,
                'unit_price' => $tierAmount,
                'total' => $tierAmount,
                'sort_order' => 0,
            ]);

            // Add-ons (amounts may be null; record as $0 items as placeholders)
            $addons = (array) ($pricing['addons'] ?? []);
            $selectedAddons = (array) ($selection?->selected_addons ?? []);
            $sort = 10;
            foreach ($addons as $a) {
                if (!is_array($a)) continue;
                $k = (string) ($a['key'] ?? '');
                if ($k === '' || !in_array($k, $selectedAddons, true)) continue;
                $label = (string) (($a['label'] ?? null) ?: $k);
                $amt = (float) (($a['amount'] ?? null) ?: 0);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Add-on: ' . $label,
                    'quantity' => 1,
                    'unit_price' => $amt,
                    'total' => $amt,
                    'sort_order' => $sort++,
                ]);
            }

            $invoice->calculateTotals();

            $proposal->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'signed_at' => now(),
                'signed_by' => (string) ($signature['name'] ?? ''),
                'signature_ip' => (string) ($signature['ip'] ?? ''),
                'signature_data' => (string) ($signature['data'] ?? ''),
                'contract_id' => $contract->id,
                'invoice_id' => $invoice->id,
            ]);

            return ['contract' => $contract, 'invoice' => $invoice];
        });
    }
}


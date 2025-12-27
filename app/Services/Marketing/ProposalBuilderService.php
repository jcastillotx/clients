<?php

namespace App\Services\Marketing;

use App\Models\Client;
use App\Models\Proposal;
use App\Models\Request as ServiceRequest;
use App\Models\RequestEstimate;
use Illuminate\Support\Str;

class ProposalBuilderService
{
    /**
     * Generate a proposal from a request + optional template key.
     *
     * @param  array<string,mixed>  $template
     */
    public function generateProposal(ServiceRequest $request, array $template = [], array $options = []): Proposal
    {
        $request->loadMissing(['client', 'attachments']);
        $client = $request->client;
        if (! $client) {
            throw new \RuntimeException('Request has no client.');
        }

        $estimate = RequestEstimate::query()
            ->where('request_id', $request->id)
            ->orderByDesc('id')
            ->first();

        $pricingData = (array) ($estimate?->pricing_data ?? []);
        $estimateData = (array) ($estimate?->estimate_data ?? []);
        $tasks = is_array($estimateData['tasks'] ?? null) ? $estimateData['tasks'] : [];

        $pricing = $this->createPricingOptions($tasks, $pricingData);

        $sections = $template['sections'] ?? [
            ['key' => 'executive_summary', 'title' => 'Executive Summary', 'body' => ''],
            ['key' => 'needs', 'title' => 'Understanding of Needs', 'body' => $request->description],
            ['key' => 'solution', 'title' => 'Proposed Solution', 'body' => ''],
            ['key' => 'scope', 'title' => 'Scope of Work', 'body' => ''],
            ['key' => 'timeline', 'title' => 'Timeline & Deliverables', 'body' => ''],
            ['key' => 'pricing', 'title' => 'Pricing Options', 'body' => ''],
            ['key' => 'terms', 'title' => 'Terms & Next Steps', 'body' => ''],
        ];

        $content = [
            'cover' => [
                'client_name' => $client->company_name,
                'title' => $template['title'] ?? ('Proposal — '.$request->title),
                'prepared_for' => $client->company_name,
                'prepared_by' => config('app.name'),
            ],
            'request' => [
                'id' => $request->id,
                'title' => $request->title,
                'type' => $request->type,
                'priority' => $request->priority,
            ],
            'sections' => $sections,
        ];

        return Proposal::create([
            'client_id' => $client->id,
            'request_id' => $request->id,
            'title' => (string) ($content['cover']['title'] ?? 'Proposal'),
            'proposal_number' => $this->newProposalNumber($client),
            'template_id' => $template['id'] ?? null,
            'content' => $content,
            'pricing_data' => $pricing,
            'status' => 'draft',
            'valid_until' => now()->addDays((int) ($options['valid_days'] ?? 14))->toDateString(),
            'created_by' => $options['created_by'] ?? null,
        ]);
    }

    /**
     * Create tiered pricing (good/better/best) + optional add-ons from tasks.
     *
     * @param  array<int, array<string,mixed>>  $services
     * @param  array<string,mixed>  $estimatePricingData
     * @return array<string,mixed>
     */
    public function createPricingOptions(array $services, array $estimatePricingData = []): array
    {
        // Base costs from estimate (if present)
        $mid = (float) (($estimatePricingData['totals']['mid']['total'] ?? null) ?: 0);
        $low = (float) (($estimatePricingData['totals']['low']['total'] ?? null) ?: 0);
        $high = (float) (($estimatePricingData['totals']['high']['total'] ?? null) ?: 0);

        // Default tiers derived from estimate bands
        $tiers = [
            'good' => ['label' => 'Good', 'amount' => $low > 0 ? $low : max(0, $mid * 0.85)],
            'better' => ['label' => 'Better', 'amount' => $mid > 0 ? $mid : max(0, $low * 1.15)],
            'best' => ['label' => 'Best', 'amount' => $high > 0 ? $high : max(0, $mid * 1.25)],
        ];

        // Optional tasks become add-ons (very lightweight mapping)
        $addons = [];
        foreach ($services as $idx => $t) {
            if (! is_array($t)) {
                continue;
            }
            if (empty($t['optional'])) {
                continue;
            }
            $name = trim((string) ($t['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $hours = (float) (($t['hours_mid'] ?? null) ?: 0);
            $addons[] = [
                'key' => 'addon_'.$idx.'_'.Str::slug($name),
                'label' => $name,
                'description' => (string) ($t['description'] ?? ''),
                'estimated_hours' => $hours,
                'amount' => null, // computed client-side when rate known; keep null for now
            ];
        }

        return [
            'tiers' => $tiers,
            'addons' => $addons,
            'payment_plans' => [
                ['key' => 'pay_full', 'label' => 'Pay in full', 'multiplier' => 1.0],
                ['key' => 'pay_2', 'label' => '2 payments', 'multiplier' => 1.02],
                ['key' => 'pay_3', 'label' => '3 payments', 'multiplier' => 1.04],
            ],
            'currency' => 'USD',
        ];
    }

    private function newProposalNumber(Client $client): string
    {
        // Human-friendly number: PROP-<clientId>-<YYYYMMDD>-<rand4>
        return 'PROP-'.$client->id.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
    }
}

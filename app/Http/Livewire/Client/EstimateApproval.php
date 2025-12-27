<?php

namespace App\Http\Livewire\Client;

use App\Models\Request as ServiceRequest;
use App\Models\RequestEstimate;
use App\Services\AI\SowGenerationService;
use App\Services\Estimates\CostCalculationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EstimateApproval extends Component
{
    public ServiceRequest $request;

    public RequestEstimate $estimate;

    /** @var array<int, array<string, mixed>> */
    public array $tasks = [];

    public string $message = '';

    public function mount(ServiceRequest $request): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless((int) $request->client_id === (int) $user->client_id, 403);

        $this->request = $request->load('client');

        $estimate = RequestEstimate::query()
            ->where('request_id', $request->id)
            ->whereIn('status', ['sent', 'changes_requested', 'draft'])
            ->orderByDesc('id')
            ->first();

        abort_unless($estimate, 404);

        $this->estimate = $estimate->load('sowContract');

        $data = (array) ($estimate->estimate_data ?? []);
        $tasks = is_array($data['tasks'] ?? null) ? $data['tasks'] : [];
        $this->tasks = $this->applyStoredSelections($tasks, (array) ($estimate->client_selections ?? []));

        $this->message = (string) (($estimate->client_message ?? '') ?: (($estimate->client_selections['message'] ?? '') ?: ''));
    }

    public function updatedTasks(): void
    {
        // Keep selections in sync.
        $this->persistSelections();
    }

    public function approve(CostCalculationService $costs, SowGenerationService $sow): mixed
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $this->persistSelections();

        $this->estimate->refresh();

        // Ensure SOW contract exists.
        if (! $this->estimate->sow_contract_id) {
            $estimateData = (array) ($this->estimate->estimate_data ?? []);
            $pricingData = (array) ($this->estimate->pricing_data ?? []);
            if (empty($pricingData)) {
                $pricingData = $this->buildPricingData($costs, $estimateData);
            }
            $sections = (array) ($pricingData['sow_sections'] ?? []);

            $contract = $sow->generateSowContract($this->request->fresh(), $this->estimate->fresh(), $estimateData, $pricingData, $sections);
            $this->estimate->refresh();
        }

        $this->estimate->update([
            'status' => 'approved',
            'approved_at' => now(),
            'client_message' => null,
        ]);

        return redirect()->route('contracts.show', $this->estimate->sow_contract_id);
    }

    public function requestChanges(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $this->estimate->update([
            'status' => 'changes_requested',
            'client_message' => trim($this->message) ?: null,
        ]);

        session()->flash('success', 'Request sent. We’ll review and follow up.');
    }

    protected function persistSelections(): void
    {
        $included = [];
        foreach ($this->tasks as $i => $t) {
            if (! is_array($t)) {
                continue;
            }
            if (! ($t['optional'] ?? false)) {
                continue;
            }
            $included[(string) $i] = (bool) ($t['included'] ?? true);
        }

        $this->estimate->update([
            'client_selections' => [
                'included' => $included,
                'message' => trim($this->message) ?: null,
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @param  array<string, mixed>  $selections
     * @return array<int, array<string, mixed>>
     */
    protected function applyStoredSelections(array $tasks, array $selections): array
    {
        $inc = is_array($selections['included'] ?? null) ? $selections['included'] : [];
        foreach ($tasks as $i => &$t) {
            if (! is_array($t)) {
                continue;
            }
            $optional = (bool) ($t['optional'] ?? false);
            if (! $optional) {
                $t['included'] = true;

                continue;
            }
            $key = (string) $i;
            if (array_key_exists($key, $inc)) {
                $t['included'] = (bool) $inc[$key];
            } else {
                $t['included'] = (bool) ($t['included'] ?? true);
            }
        }
        unset($t);

        return $tasks;
    }

    /**
     * @param  array<string,mixed>  $estimateData
     * @return array<string,mixed>
     */
    protected function buildPricingData(CostCalculationService $costs, array $estimateData): array
    {
        $base = (float) (($estimateData['base_rate'] ?? 0) ?: $costs->defaultBaseRate());
        $markup = (float) (($estimateData['markup_pct'] ?? 0) ?: $costs->defaultMarkupPct());
        $cont = (float) (($estimateData['contingency_pct'] ?? 0) ?: $costs->contingencyPctForComplexity((int) ($estimateData['complexity_score'] ?? 5)));

        $sum = ['low' => 0.0, 'mid' => 0.0, 'high' => 0.0];
        foreach ($this->tasks as $t) {
            if (! is_array($t)) {
                continue;
            }
            if (! ($t['included'] ?? true)) {
                continue;
            }
            $sum['low'] += (float) ($t['hours_low'] ?? 0);
            $sum['mid'] += (float) ($t['hours_mid'] ?? 0);
            $sum['high'] += (float) ($t['hours_high'] ?? 0);
        }

        $client = $this->request->client;
        $totals = [
            'low' => $costs->calculate($sum['low'], $base, $client, $markup, $cont),
            'mid' => $costs->calculate($sum['mid'], $base, $client, $markup, $cont),
            'high' => $costs->calculate($sum['high'], $base, $client, $markup, $cont),
        ];

        return [
            'totals' => $totals,
            'cost_range' => [
                'low' => number_format((float) $totals['low']['total'], 2, '.', ''),
                'mid' => number_format((float) $totals['mid']['total'], 2, '.', ''),
                'high' => number_format((float) $totals['high']['total'], 2, '.', ''),
            ],
        ];
    }

    public function render(CostCalculationService $costs)
    {
        $pricing = (array) ($this->estimate->pricing_data ?? []);
        if (empty($pricing) || empty($pricing['totals'])) {
            $pricing = $this->buildPricingData($costs, (array) ($this->estimate->estimate_data ?? []));
        } else {
            // Recompute totals live from current selections.
            $pricing = $this->buildPricingData($costs, (array) ($this->estimate->estimate_data ?? []));
        }

        return view('livewire.client.estimate-approval', [
            'pricing' => $pricing,
            'contract' => $this->estimate->sowContract,
        ]);
    }
}

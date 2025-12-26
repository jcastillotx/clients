<?php

namespace App\Http\Livewire\Admin\Requests;

use App\Models\Request as ServiceRequest;
use App\Models\RequestEstimate;
use App\Notifications\EstimateSentForApprovalNotification;
use App\Services\AI\ProjectEstimationService;
use App\Services\AI\SowGenerationService;
use App\Services\Estimates\CostCalculationService;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class ProjectEstimator extends Component
{
    public ServiceRequest $request;

    public ?RequestEstimate $estimate = null;

    /** @var array<int, array<string, mixed>> */
    public array $tasks = [];
    public array $timeline = [];
    public array $risk_factors = [];

    public int $complexity_score = 5;
    public float $base_rate = 100.0;
    public float $markup_pct = 0.2;
    public float $contingency_pct = 0.1;

    /** SOW sections (AI drafted) */
    public array $sow_sections = [];

    public function mount(ServiceRequest $request, CostCalculationService $costs): void
    {
        $this->request = $request->load(['client']);

        $this->base_rate = $costs->defaultBaseRate();
        $this->markup_pct = $costs->defaultMarkupPct();
        $this->contingency_pct = $costs->contingencyPctForComplexity($this->complexity_score);

        $this->loadLatestEstimate();
    }

    public function loadLatestEstimate(): void
    {
        $this->estimate = RequestEstimate::query()
            ->where('request_id', $this->request->id)
            ->orderByDesc('id')
            ->first();

        $data = (array) ($this->estimate?->estimate_data ?? []);
        $this->tasks = is_array($data['tasks'] ?? null) ? $data['tasks'] : [];
        $this->timeline = is_array($data['timeline'] ?? null) ? $data['timeline'] : [];
        $this->risk_factors = is_array($data['risk_factors'] ?? null) ? $data['risk_factors'] : [];

        $this->complexity_score = (int) (($data['complexity_score'] ?? $this->complexity_score) ?: $this->complexity_score);
        $this->base_rate = (float) (($data['base_rate'] ?? $this->base_rate) ?: $this->base_rate);
        $this->markup_pct = (float) (($data['markup_pct'] ?? $this->markup_pct) ?: $this->markup_pct);
        $this->contingency_pct = (float) (($data['contingency_pct'] ?? $this->contingency_pct) ?: $this->contingency_pct);

        $pricing = (array) ($this->estimate?->pricing_data ?? []);
        $this->sow_sections = is_array($pricing['sow_sections'] ?? null) ? $pricing['sow_sections'] : [];
    }

    public function generateAiEstimate(ProjectEstimationService $ai, CostCalculationService $costs): void
    {
        $result = $ai->generateEstimate($this->request->fresh()->load(['client', 'attachments']), [
            'base_rate' => $this->base_rate,
            'markup_pct' => $this->markup_pct,
            'contingency_pct' => $this->contingency_pct,
            'complexity_score' => $this->complexity_score,
            'executed_by' => auth()->id(),
            'complexity' => 'high',
        ]);

        $this->tasks = $this->normalizeTasks((array) ($result['tasks'] ?? []));
        $this->timeline = (array) ($result['timeline'] ?? []);
        $this->risk_factors = (array) ($result['risk_factors'] ?? []);

        $pricingData = $this->buildPricingData($costs, $result);

        $this->estimate = RequestEstimate::create([
            'request_id' => $this->request->id,
            'client_id' => $this->request->client_id,
            'created_by' => auth()->id(),
            'status' => 'draft',
            'ai_task_id' => $result['_meta']['task_id'] ?? null,
            'estimate_data' => [
                'tasks' => $this->tasks,
                'timeline' => $this->timeline,
                'risk_factors' => $this->risk_factors,
                'similar_projects' => $result['similar_projects'] ?? [],
                'historical_variance' => $result['historical_variance'] ?? [],
                'base_rate' => $this->base_rate,
                'markup_pct' => $this->markup_pct,
                'contingency_pct' => $this->contingency_pct,
                'complexity_score' => $this->complexity_score,
            ],
            'pricing_data' => $pricingData,
        ]);

        session()->flash('success', 'AI estimate generated.');
    }

    public function saveEstimate(CostCalculationService $costs): void
    {
        $this->tasks = $this->normalizeTasks($this->tasks);

        $pricingData = $this->buildPricingData($costs, [
            'tasks' => $this->tasks,
            'timeline' => $this->timeline,
            'risk_factors' => $this->risk_factors,
        ]);

        if (!$this->estimate) {
            $this->estimate = RequestEstimate::create([
                'request_id' => $this->request->id,
                'client_id' => $this->request->client_id,
                'created_by' => auth()->id(),
                'status' => 'draft',
                'estimate_data' => [],
                'pricing_data' => [],
            ]);
        }

        $this->estimate->update([
            'estimate_data' => [
                'tasks' => $this->tasks,
                'timeline' => $this->timeline,
                'risk_factors' => $this->risk_factors,
                'base_rate' => $this->base_rate,
                'markup_pct' => $this->markup_pct,
                'contingency_pct' => $this->contingency_pct,
                'complexity_score' => $this->complexity_score,
                'similar_projects' => $this->estimate?->estimate_data['similar_projects'] ?? [],
                'historical_variance' => $this->estimate?->estimate_data['historical_variance'] ?? [],
            ],
            'pricing_data' => $pricingData,
        ]);

        session()->flash('success', 'Estimate saved.');
    }

    public function draftSow(SowGenerationService $sow, CostCalculationService $costs): void
    {
        if (!$this->estimate) {
            session()->flash('error', 'Save an estimate first.');
            return;
        }

        $estimateData = (array) ($this->estimate->estimate_data ?? []);
        $pricingData = (array) ($this->estimate->pricing_data ?? []);
        $pricingData = $pricingData ?: $this->buildPricingData($costs, $estimateData);

        $sections = $sow->draftSowSections($this->request->fresh()->load('client'), $estimateData, $pricingData, [
            'executed_by' => auth()->id(),
            'complexity' => 'medium',
        ]);

        $pricingData['sow_sections'] = $sections;
        $this->estimate->update(['pricing_data' => $pricingData]);
        $this->sow_sections = $sections;

        session()->flash('success', 'SOW sections drafted.');
    }

    public function generateSowPdf(SowGenerationService $sow, CostCalculationService $costs): void
    {
        if (!$this->estimate) {
            session()->flash('error', 'Save an estimate first.');
            return;
        }

        $estimateData = (array) ($this->estimate->estimate_data ?? []);
        $pricingData = (array) ($this->estimate->pricing_data ?? []);
        $pricingData = $pricingData ?: $this->buildPricingData($costs, $estimateData);

        $sections = (array) ($pricingData['sow_sections'] ?? $this->sow_sections ?? []);

        $contract = $sow->generateSowContract($this->request->fresh(), $this->estimate->fresh(), $estimateData, $pricingData, $sections);

        $this->estimate->refresh();
        session()->flash('success', 'SOW PDF generated. Contract #' . $contract->contract_number . ' is pending signature.');
    }

    public function sendToClient(): void
    {
        if (!$this->estimate) {
            session()->flash('error', 'Save an estimate first.');
            return;
        }

        $client = $this->request->client;
        if (!$client) {
            session()->flash('error', 'Request has no client.');
            return;
        }

        $this->estimate->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $recipients = $client->users()->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new EstimateSentForApprovalNotification($this->estimate->fresh()->load('request')));
        }

        session()->flash('success', 'Estimate sent to client for approval.');
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeTasks(array $tasks): array
    {
        $out = [];
        foreach ($tasks as $t) {
            if (!is_array($t)) continue;
            $name = trim((string) ($t['name'] ?? ''));
            if ($name === '') continue;

            $hoursLow = (float) ($t['hours_low'] ?? 0);
            $hoursMid = (float) ($t['hours_mid'] ?? 0);
            $hoursHigh = (float) ($t['hours_high'] ?? 0);

            // If only mid is set, auto-derive low/high.
            if ($hoursMid > 0 && ($hoursLow <= 0 || $hoursHigh <= 0)) {
                $hoursLow = $hoursLow > 0 ? $hoursLow : max(0, $hoursMid * 0.8);
                $hoursHigh = $hoursHigh > 0 ? $hoursHigh : max($hoursMid, $hoursMid * 1.25);
            }

            // Ensure ordering low <= mid <= high
            $hoursLow = max(0.0, min($hoursLow, $hoursMid > 0 ? $hoursMid : $hoursLow));
            $hoursHigh = max($hoursHigh, $hoursMid, $hoursLow);
            $hoursMid = max($hoursMid, $hoursLow);

            $optional = (bool) ($t['optional'] ?? false);
            $included = $optional ? (bool) ($t['included'] ?? true) : true;

            $out[] = [
                'name' => $name,
                'description' => (string) ($t['description'] ?? ''),
                'phase' => (string) ($t['phase'] ?? ''),
                'optional' => $optional,
                'included' => $included,
                'hours_low' => $hoursLow,
                'hours_mid' => $hoursMid,
                'hours_high' => $hoursHigh,
            ];
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $estimateLike
     * @return array<string,mixed>
     */
    protected function buildPricingData(CostCalculationService $costs, array $estimateLike): array
    {
        $tasks = is_array($estimateLike['tasks'] ?? null) ? $estimateLike['tasks'] : $this->tasks;
        $tasks = $this->normalizeTasks($tasks);

        $sum = ['low' => 0.0, 'mid' => 0.0, 'high' => 0.0];
        foreach ($tasks as $t) {
            if (!($t['included'] ?? true)) continue;
            $sum['low'] += (float) ($t['hours_low'] ?? 0);
            $sum['mid'] += (float) ($t['hours_mid'] ?? 0);
            $sum['high'] += (float) ($t['hours_high'] ?? 0);
        }

        $client = $this->request->client;
        $totals = [
            'low' => $costs->calculate($sum['low'], $this->base_rate, $client, $this->markup_pct, $this->contingency_pct),
            'mid' => $costs->calculate($sum['mid'], $this->base_rate, $client, $this->markup_pct, $this->contingency_pct),
            'high' => $costs->calculate($sum['high'], $this->base_rate, $client, $this->markup_pct, $this->contingency_pct),
        ];

        return [
            'base_rate' => $this->base_rate,
            'markup_pct' => $this->markup_pct,
            'contingency_pct' => $this->contingency_pct,
            'complexity_score' => $this->complexity_score,
            'sow_sections' => $this->sow_sections,
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
        $pricing = $this->buildPricingData($costs, [
            'tasks' => $this->tasks,
        ]);

        $hist = (array) ($this->estimate?->estimate_data['historical_variance'] ?? []);
        $similar = (array) ($this->estimate?->estimate_data['similar_projects'] ?? []);

        $avgEstimated = null;
        $avgActual = null;
        if (!empty($similar)) {
            $ests = [];
            $acts = [];
            foreach ($similar as $p) {
                if (is_array($p) && isset($p['estimated_hours']) && is_numeric($p['estimated_hours'])) $ests[] = (float) $p['estimated_hours'];
                if (is_array($p) && isset($p['actual_hours']) && is_numeric($p['actual_hours'])) $acts[] = (float) $p['actual_hours'];
            }
            if (count($ests) > 0) $avgEstimated = array_sum($ests) / count($ests);
            if (count($acts) > 0) $avgActual = array_sum($acts) / count($acts);
        }

        return view('livewire.admin.requests.project-estimator', [
            'estimateRecord' => $this->estimate,
            'pricing' => $pricing,
            'historicalVariance' => $hist,
            'avgEstimated' => $avgEstimated,
            'avgActual' => $avgActual,
        ]);
    }
}


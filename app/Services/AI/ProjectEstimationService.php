<?php

namespace App\Services\AI;

use App\Models\AiTask;
use App\Models\Request as ServiceRequest;
use App\Models\Setting;
use App\Services\AI\Prompts\ProjectEstimationPrompt;
use App\Services\Estimates\CostCalculationService;
use Illuminate\Support\Facades\Log;

class ProjectEstimationService
{
    public function __construct(
        protected AIProviderManager $providers,
        protected RequestEmbeddingService $embeddings,
        protected RequestSemanticSearchService $semantic,
        protected CostCalculationService $costs
    ) {}

    /**
     * Generate a scoped estimate with tasks/hours, cost range, and risk factors.
     *
     * @return array<string, mixed>
     */
    public function generateEstimate(ServiceRequest $request, array $options = []): array
    {
        $request->loadMissing(['client', 'attachments']);

        // Base rate is from internal rate card / settings.
        $baseRate = is_numeric($options['base_rate'] ?? null)
            ? (float) $options['base_rate']
            : $this->costs->defaultBaseRate();

        $markupPct = is_numeric($options['markup_pct'] ?? null)
            ? max(0.0, (float) $options['markup_pct'])
            : $this->costs->defaultMarkupPct();

        $complexity = (int) ($options['complexity_score'] ?? 5);
        $contingencyPct = is_numeric($options['contingency_pct'] ?? null)
            ? max(0.0, (float) $options['contingency_pct'])
            : $this->costs->contingencyPctForComplexity($complexity);

        $task = AiTask::create([
            'task_type' => 'generate_estimate',
            'input_data' => [
                'request_id' => $request->id,
                'base_rate' => $baseRate,
                'markup_pct' => $markupPct,
                'complexity_score' => $complexity,
                'contingency_pct' => $contingencyPct,
            ],
            'status' => 'processing',
            'executed_by' => $options['executed_by'] ?? null,
        ]);

        $similar = $this->similarProjects($request, $task->id);
        $variance = $this->semantic->varianceStats(array_map(fn ($p) => [
            'estimated_hours' => $p['estimated_hours'] ?? null,
            'actual_hours' => $p['actual_hours'] ?? null,
        ], $similar));

        $payloadRequest = [
            'id' => $request->id,
            'client' => $request->client?->company_name ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'priority' => $request->priority,
            'attachments' => $request->attachments?->map(fn ($a) => [
                'name' => $a->original_filename,
                'mime' => $a->mime_type,
                'size' => (int) $a->file_size,
            ])->values()->all() ?? [],
        ];

        $messages = [
            ['role' => 'system', 'content' => ProjectEstimationPrompt::systemPrompt()],
            ['role' => 'user', 'content' => ProjectEstimationPrompt::userPrompt($payloadRequest, $similar, $variance, $baseRate)],
        ];

        try {
            $target = $this->providers->routeToOptimalProvider('generate_estimate', (string) ($options['complexity'] ?? 'high'));
            $preferred = (string) ($options['provider'] ?? $target['provider'] ?? 'openai');
            $model = (string) ($options['model'] ?? $target['model'] ?? '');

            $res = $this->providers->withFallback($preferred, function ($provider) use ($messages, $task, $request, $model) {
                return $provider->chat($messages, [
                    'model' => $model !== '' ? $model : null,
                    'task_type' => 'generate_estimate',
                    'client_id' => $request->client_id,
                    'user_id' => $request->created_by,
                    'timeout' => 120,
                    'task_id' => $task->id,
                ]);
            }, 'generate_estimate');

            $estimate = $this->parseJsonFromText((string) ($res['text'] ?? ''));
            $totals = $this->computeTotals($estimate['tasks'] ?? []);
            $costRange = $this->computeCostRange($totals, $baseRate);

            $client = $request->client;
            $pricingTotals = [
                'low' => $this->costs->calculate((float) $totals['low'], $baseRate, $client, $markupPct, $contingencyPct),
                'mid' => $this->costs->calculate((float) $totals['mid'], $baseRate, $client, $markupPct, $contingencyPct),
                'high' => $this->costs->calculate((float) $totals['high'], $baseRate, $client, $markupPct, $contingencyPct),
            ];

            $final = [
                'tasks' => $estimate['tasks'] ?? [],
                'timeline' => $estimate['timeline'] ?? [],
                'risk_factors' => $estimate['risk_factors'] ?? [],
                'notes_for_admin' => $estimate['notes_for_admin'] ?? null,
                'similar_projects' => $similar,
                'historical_variance' => $variance,
                'base_rate' => $baseRate,
                'markup_pct' => $markupPct,
                'contingency_pct' => $contingencyPct,
                'complexity_score' => $complexity,
                'hours_total' => $totals,
                'cost_range' => $costRange,
                'totals' => $pricingTotals,
                '_meta' => [
                    'task_id' => $task->id,
                    'provider' => $res['provider'] ?? null,
                    'model' => $res['model'] ?? null,
                    'tokens' => $res['tokens'] ?? null,
                    'estimated_ai_cost' => $res['estimated_cost'] ?? null,
                ],
            ];

            $task->update([
                'output_data' => $final,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $final;
        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'output_data' => ['error' => $e->getMessage()],
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a professional SOW document using a company template.
     *
     * @param  array<string, mixed>  $estimate
     */
    public function generateSOW(ServiceRequest $request, array $estimate): string
    {
        $request->loadMissing(['client']);

        $clientName = $request->client?->company_name ?? ('Client #'.(int) $request->client_id);
        $tasks = is_array($estimate['tasks'] ?? null) ? $estimate['tasks'] : [];

        $deliverables = [];
        foreach ($tasks as $t) {
            if (! is_array($t) || empty($t['name'])) {
                continue;
            }
            $deliverables[] = (string) $t['name'];
        }
        if (empty($deliverables)) {
            $deliverables = ['Deliverables to be confirmed after discovery.'];
        }

        $assumptions = [];
        $outOfScope = [];
        foreach ($tasks as $t) {
            if (! is_array($t)) {
                continue;
            }
            foreach (($t['assumptions'] ?? []) as $a) {
                $assumptions[] = (string) $a;
            }
            foreach (($t['out_of_scope'] ?? []) as $o) {
                $outOfScope[] = (string) $o;
            }
        }
        $assumptions = array_values(array_unique(array_filter($assumptions)));
        $outOfScope = array_values(array_unique(array_filter($outOfScope)));

        $timeline = (array) ($estimate['timeline'] ?? []);
        $hours = (array) ($estimate['hours_total'] ?? ['low' => 0, 'mid' => 0, 'high' => 0]);
        $cost = (array) ($estimate['cost_range'] ?? ['low' => '0.00', 'mid' => '0.00', 'high' => '0.00']);
        $hourlyRate = (float) (($estimate['base_rate'] ?? $estimate['hourly_rate'] ?? null) ?: Setting::getValue('billing.hourly_rate', 100));

        $objectives = trim((string) ($request->description ?? ''));
        if ($objectives === '') {
            $objectives = 'Objectives to be confirmed.';
        }

        // Keep it simple: we store as plain text; if you want richer markdown later we can add an AI "polish" pass.
        $scopeMarkdown = '';
        if (! empty($estimate['notes_for_admin'])) {
            $scopeMarkdown = e((string) $estimate['notes_for_admin']);
        }
        $scopeMarkdown = nl2br($scopeMarkdown);

        return (string) view('docs.sow-template', [
            'request' => $request,
            'clientName' => $clientName,
            'projectTitle' => $request->title,
            'date' => now()->toDateString(),
            'objectives' => $objectives,
            'deliverables' => $deliverables,
            'scopeMarkdown' => $scopeMarkdown,
            'timeline' => [
                'low' => (string) ($timeline['duration_weeks_low'] ?? '—'),
                'mid' => (string) ($timeline['duration_weeks_mid'] ?? '—'),
                'high' => (string) ($timeline['duration_weeks_high'] ?? '—'),
            ],
            'milestones' => $timeline['milestones'] ?? [],
            'hourlyRate' => $hourlyRate,
            'hours' => [
                'low' => number_format((float) ($hours['low'] ?? 0), 1),
                'mid' => number_format((float) ($hours['mid'] ?? 0), 1),
                'high' => number_format((float) ($hours['high'] ?? 0), 1),
            ],
            'cost' => [
                'low' => (string) ($cost['low'] ?? '0.00'),
                'mid' => (string) ($cost['mid'] ?? '0.00'),
                'high' => (string) ($cost['high'] ?? '0.00'),
            ],
            'tasks' => $tasks,
            'assumptions' => ! empty($assumptions) ? $assumptions : ['Assumptions to be confirmed.'],
            'outOfScope' => ! empty($outOfScope) ? $outOfScope : ['Out of scope items to be confirmed.'],
            'risks' => $estimate['risk_factors'] ?? ['Risks to be confirmed.'],
        ])->render();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function similarProjects(ServiceRequest $request, int $taskId): array
    {
        // Best-effort embedding-based semantic search, falls back to recent keyword search.
        $vec = $this->embeddings->embedText($this->embeddings->contentForEmbedding($request), [
            'provider' => 'openai',
            'model' => 'text-embedding-3-small',
            'task_id' => $taskId,
        ]);

        if ($vec) {
            $ranked = $this->semantic->findSimilarByEmbedding($vec, 5, 500, $request->id);
            $ids = array_map(fn ($r) => (int) $r['request_id'], $ranked);
            if (! empty($ids)) {
                $rows = ServiceRequest::query()
                    ->whereIn('id', $ids)
                    ->get(['id', 'title', 'type', 'priority', 'estimated_hours', 'actual_hours'])
                    ->keyBy('id');

                $out = [];
                foreach ($ranked as $r) {
                    $row = $rows->get((int) $r['request_id']);
                    if (! $row) {
                        continue;
                    }
                    $out[] = [
                        'id' => (int) $row->id,
                        'title' => (string) $row->title,
                        'type' => (string) $row->type,
                        'priority' => (string) $row->priority,
                        'estimated_hours' => $row->estimated_hours !== null ? (float) $row->estimated_hours : null,
                        'actual_hours' => $row->actual_hours !== null ? (float) $row->actual_hours : null,
                        'similarity' => (float) $r['score'],
                    ];
                }

                return $out;
            }
        }

        // Fallback: last 10 similar keywords in title/description.
        $kw = array_values(array_filter(preg_split('/\s+/', strtolower((string) $request->title))));
        $kw = array_slice($kw, 0, 6);
        $q = ServiceRequest::query()->where('id', '!=', $request->id);
        foreach ($kw as $w) {
            $q->orWhere('title', 'like', '%'.$w.'%')->orWhere('description', 'like', '%'.$w.'%');
        }

        return $q->orderByDesc('id')->limit(5)->get(['id', 'title', 'type', 'priority', 'estimated_hours', 'actual_hours'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'type' => (string) $row->type,
                'priority' => (string) $row->priority,
                'estimated_hours' => $row->estimated_hours !== null ? (float) $row->estimated_hours : null,
                'actual_hours' => $row->actual_hours !== null ? (float) $row->actual_hours : null,
                'similarity' => null,
            ])->values()->all();
    }

    /**
     * @param  array<int, mixed>  $tasks
     * @return array{low:float, mid:float, high:float}
     */
    protected function computeTotals(array $tasks): array
    {
        $sum = ['low' => 0.0, 'mid' => 0.0, 'high' => 0.0];
        foreach ($tasks as $t) {
            if (! is_array($t)) {
                continue;
            }
            $sum['low'] += (float) ($t['hours_low'] ?? 0);
            $sum['mid'] += (float) ($t['hours_mid'] ?? 0);
            $sum['high'] += (float) ($t['hours_high'] ?? 0);
        }

        return $sum;
    }

    /**
     * @return array{low:string, mid:string, high:string}
     */
    protected function computeCostRange(array $hours, float $hourlyRate): array
    {
        $low = max(0, (float) ($hours['low'] ?? 0)) * $hourlyRate;
        $mid = max(0, (float) ($hours['mid'] ?? 0)) * $hourlyRate;
        $high = max(0, (float) ($hours['high'] ?? 0)) * $hourlyRate;

        return [
            'low' => number_format($low, 2, '.', ''),
            'mid' => number_format($mid, 2, '.', ''),
            'high' => number_format($high, 2, '.', ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseJsonFromText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('AI estimation returned non-JSON output.');

        return [];
    }
}

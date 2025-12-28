<?php

namespace App\Http\Livewire\Admin\Requests;

use App\Jobs\Ai\AnalyzeRequestJob;
use App\Models\AiTask;
use App\Models\Request as ServiceRequest;
use App\Services\AI\RequestTriageService;
use Livewire\Component;

class AIRequestAnalysis extends Component
{
    public ServiceRequest $request;

    public string $provider = 'openai';

    public string $model = '';

    public ?array $analysis = null; // latest output_data

    public ?string $analysisStatus = null; // completed|failed|processing|null

    public ?float $analysisCost = null;

    public ?int $analysisTaskId = null;

    // Inline edits / overrides
    public string $suggested_type = '';

    public string $suggested_priority = '';

    public string $suggested_estimated_hours = '';

    public function mount(ServiceRequest $request): void
    {
        $this->request = $request;
        $this->loadLatest();
    }

    public function loadLatest(): void
    {
        $task = $this->latestTask();

        if (! $task) {
            $this->analysis = null;
            $this->analysisStatus = null;
            $this->analysisCost = null;
            $this->analysisTaskId = null;

            return;
        }

        $this->analysisTaskId = (int) $task->id;
        $this->analysisStatus = (string) $task->status;
        $this->analysis = is_array($task->output_data) ? $task->output_data : null;
        $this->analysisCost = $task->cost !== null ? (float) $task->cost : $this->extractCostFromAnalysis($this->analysis);

        $triage = (array) ($this->analysis['triage'] ?? $this->analysis ?? []);
        $this->suggested_type = (string) ($triage['suggested_request_type'] ?? $this->request->type);
        $this->suggested_priority = (string) ($triage['suggested_priority'] ?? $this->request->priority);
        $est = $triage['estimated_hours'] ?? $this->request->estimated_hours;
        $this->suggested_estimated_hours = $est !== null ? (string) $est : '';
    }

    public function runAnalysis(): void
    {
        AnalyzeRequestJob::dispatch($this->request->id, [
            'provider' => $this->provider ?: null,
            'model' => $this->model ?: null,
        ]);

        session()->flash('success', 'AI analysis queued.');
        $this->loadLatest();
    }

    public function acceptAiSuggestions(): void
    {
        $task = $this->latestTask();
        if (! $task || ! is_array($task->output_data)) {
            session()->flash('error', 'No AI analysis to apply yet.');

            return;
        }

        $analysis = (array) $task->output_data;
        $triage = (array) ($analysis['triage'] ?? $analysis);

        app(RequestTriageService::class)->applyTriage($this->request->fresh(), $triage);

        session()->flash('success', 'AI suggestions applied (conservatively).');
        $this->request->refresh();
        $this->loadLatest();
    }

    public function saveOverrides(): void
    {
        $type = trim($this->suggested_type);
        $priority = trim($this->suggested_priority);
        $hours = trim($this->suggested_estimated_hours);

        $updates = [];
        if ($type !== '') {
            $updates['type'] = $type;
        }
        if ($priority !== '') {
            $updates['priority'] = $priority;
        }
        $updates['estimated_hours'] = $hours !== '' ? (float) $hours : null;

        $this->request->update($updates);
        $this->request->refresh();

        session()->flash('success', 'Overrides saved.');
    }

    protected function latestTask(): ?AiTask
    {
        // Cross-DB compatible lookup: input_data JSON contains request_id.
        $needle = '"request_id":'.(int) $this->request->id;

        /** @var AiTask|null $task */
        $task = AiTask::query()
            ->whereIn('task_type', ['analyze_request', 'triage_request'])
            ->where('input_data', 'like', '%'.$needle.'%')
            ->orderByDesc('id')
            ->first();

        return $task;
    }

    protected function extractCostFromAnalysis(?array $analysis): ?float
    {
        if (! $analysis) {
            return null;
        }
        $triage = (array) ($analysis['triage'] ?? $analysis);
        $meta = (array) ($triage['_meta'] ?? []);
        if (isset($meta['estimated_cost']) && is_numeric($meta['estimated_cost'])) {
            return (float) $meta['estimated_cost'];
        }

        return null;
    }

    public function render()
    {
        return view('livewire.admin.requests.ai-analysis', [
            'statusLabels' => config('client-portal.request_statuses', []),
            'typeLabels' => config('client-portal.request_types', []),
            'priorityLabels' => config('client-portal.request_priorities', []),
            'providers' => ['openai', 'claude', 'openrouter', 'perplexity', 'asksage'],
        ])->layout('layouts.admin');
    }
}

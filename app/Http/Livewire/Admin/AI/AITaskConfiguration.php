<?php

namespace App\Http\Livewire\Admin\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AITaskConfiguration extends Component
{
    public array $taskModels = [];
    public string $fallbackOrderCsv = 'openai,openrouter,claude,perplexity,asksage';

    public bool $globalEnabled = true;
    public array $featureToggles = [];

    public ?string $monthlyBudgetUsd = null;
    public string $alertPct = '0.8';
    public bool $disableWhenExceeded = false;

    public function mount(): void
    {
        $this->globalEnabled = (bool) Setting::getValue('ai.features.global_enabled', true);
        $this->featureToggles = (array) (Setting::getValue('ai.features.tasks', []) ?? []);

        $this->taskModels = (array) (Setting::getValue('ai.task_models', []) ?? []);
        if (empty($this->taskModels)) {
            // Sensible defaults per request.
            $this->taskModels = [
                'triage_request' => ['provider' => 'claude', 'model' => 'claude-3-5-sonnet-latest'],
                'document_analysis' => ['provider' => 'claude', 'model' => 'claude-3-5-sonnet-latest'],
                'research' => ['provider' => 'perplexity', 'model' => 'sonar-pro'],
                'quick_response' => ['provider' => 'openrouter', 'model' => 'openai/gpt-4o-mini'],
                'creative_brainstorm' => ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            ];
        }

        $fallback = Setting::getValue('ai.fallback.order', null);
        if (is_array($fallback) && !empty($fallback)) {
            $this->fallbackOrderCsv = implode(',', array_values(array_map('strval', $fallback)));
        }

        $limit = Setting::getValue('ai.budget.monthly_limit_usd', null);
        $this->monthlyBudgetUsd = $limit !== null ? (string) $limit : null;
        $this->alertPct = (string) (Setting::getValue('ai.budget.alert_pct', 0.8) ?? '0.8');
        $this->disableWhenExceeded = (bool) (Setting::getValue('ai.budget.disable_when_exceeded', false) ?? false);
    }

    public function save(): void
    {
        $this->authorizeAdmin();

        Setting::setValue('ai.features.global_enabled', (bool) $this->globalEnabled, false, Auth::id(), 'ai');
        Setting::setValue('ai.features.tasks', $this->featureToggles, false, Auth::id(), 'ai');

        Setting::setValue('ai.task_models', $this->taskModels, false, Auth::id(), 'ai');

        $order = array_values(array_filter(array_map('trim', explode(',', (string) $this->fallbackOrderCsv))));
        Setting::setValue('ai.fallback.order', $order, false, Auth::id(), 'ai');

        Setting::setValue('ai.budget.monthly_limit_usd', $this->monthlyBudgetUsd !== null && $this->monthlyBudgetUsd !== '' ? (float) $this->monthlyBudgetUsd : 0.0, false, Auth::id(), 'ai');
        Setting::setValue('ai.budget.alert_pct', (float) $this->alertPct, false, Auth::id(), 'ai');
        Setting::setValue('ai.budget.disable_when_exceeded', (bool) $this->disableWhenExceeded, false, Auth::id(), 'ai');

        session()->flash('success', 'AI configuration saved.');
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (!$u || !$u->can('access admin panel')) {
            abort(403);
        }
    }

    public function render()
    {
        $providerOptions = [
            'openai' => 'OpenAI',
            'claude' => 'Claude',
            'openrouter' => 'OpenRouter',
            'perplexity' => 'Perplexity',
            'asksage' => 'AskSage',
        ];

        $knownTasks = [
            'triage_request' => 'Request triage',
            'document_analysis' => 'Document/contract analysis',
            'research' => 'Research (web-grounded)',
            'quick_response' => 'Quick responses',
            'creative_brainstorm' => 'Creative writing/brainstorming',
            'generate_estimate' => 'Project estimation',
            'draft_sow' => 'SOW drafting',
            'generate_contract' => 'Contract generation',
            'client_qa' => 'Client Q&A',
            'seo_research' => 'SEO/content research',
            'analytics_report' => 'Analytics narrative reports',
            'architecture_review' => 'Architecture review',
            'tech_recommendations' => 'Tech recommendations',
        ];

        return view('livewire.admin.ai.task-configuration', [
            'providerOptions' => $providerOptions,
            'knownTasks' => $knownTasks,
        ])->layout('layouts.admin', ['title' => 'AI Task Configuration']);
    }
}


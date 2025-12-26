<?php

namespace App\Http\Livewire\Admin\Automation;

use App\Models\AutomationLog;
use App\Models\AutomationRule;
use Livewire\Component;
use Livewire\WithPagination;

class AutomationLogs extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $status = 'all';
    public string $trigger = 'all';
    public ?int $ruleId = null;

    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingTrigger(): void { $this->resetPage(); }
    public function updatingRuleId(): void { $this->resetPage(); }

    public function render()
    {
        $q = AutomationLog::query()->with('rule')->orderByDesc('id');

        if ($this->status !== 'all') {
            $q->where('status', $this->status);
        }
        if ($this->trigger !== 'all') {
            $q->where('trigger', $this->trigger);
        }
        if ($this->ruleId) {
            $q->where('automation_rule_id', $this->ruleId);
        }

        $logs = $q->paginate(30);

        $triggers = AutomationLog::query()
            ->select('trigger')
            ->distinct()
            ->orderBy('trigger')
            ->pluck('trigger')
            ->all();

        $rules = AutomationRule::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.automation.logs', [
            'logs' => $logs,
            'triggers' => $triggers,
            'rules' => $rules,
        ])->layout('layouts.admin', ['title' => 'Automation Logs']);
    }
}


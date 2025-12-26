<?php

namespace App\Http\Livewire\Admin\Automation;

use App\Models\AutomationRule;
use Livewire\Component;
use Livewire\WithPagination;

class AutomationIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $trigger = 'all';
    public string $status = 'all'; // all|active|disabled

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTrigger(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }

    public function toggle(int $ruleId): void
    {
        $rule = AutomationRule::query()->findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function delete(int $ruleId): void
    {
        AutomationRule::query()->whereKey($ruleId)->delete();
        session()->flash('success', 'Automation deleted.');
    }

    public function render()
    {
        $q = AutomationRule::query()->orderBy('trigger')->orderBy('run_order')->orderBy('id');

        if ($this->search !== '') {
            $s = '%' . $this->search . '%';
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', $s)->orWhere('description', 'like', $s)->orWhere('trigger', 'like', $s);
            });
        }

        if ($this->trigger !== 'all') {
            $q->where('trigger', $this->trigger);
        }

        if ($this->status === 'active') {
            $q->where('is_active', true);
        } elseif ($this->status === 'disabled') {
            $q->where('is_active', false);
        }

        $rules = $q->paginate(20);

        $triggers = AutomationRule::query()
            ->select('trigger')
            ->distinct()
            ->orderBy('trigger')
            ->pluck('trigger')
            ->all();

        return view('livewire.admin.automation.index', [
            'rules' => $rules,
            'triggers' => $triggers,
        ])->layout('layouts.admin', ['title' => 'Automation']);
    }
}


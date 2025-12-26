<?php

namespace App\Http\Livewire\Admin\Automation;

use App\Models\AutomationRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AutomationLogs extends Component
{
    public ?string $trigger = '';
    public ?int $ruleId = null;

    protected array $queryString = [
        'trigger' => ['except' => ''],
        'ruleId' => ['except' => null],
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('access admin panel'), 403);
    }

    public function render()
    {
        $runs = AutomationRun::query()
            ->with('rule')
            ->when($this->trigger, fn ($q) => $q->where('trigger', $this->trigger))
            ->when($this->ruleId, fn ($q) => $q->where('automation_rule_id', $this->ruleId))
            ->latest('id')
            ->paginate(25);

        return view('livewire.admin.automation.logs', compact('runs'));
    }
}


<?php

namespace App\Http\Livewire\Admin\Automation;

use App\Models\AutomationRule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AutomationIndex extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('access admin panel'), 403);
    }

    public function toggle(int $id): void
    {
        abort_unless(Auth::user()?->can('access admin panel'), 403);
        $rule = AutomationRule::query()->findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function delete(int $id): void
    {
        abort_unless(Auth::user()?->can('access admin panel'), 403);
        AutomationRule::query()->whereKey($id)->delete();
        session()->flash('success', 'Automation deleted.');
    }

    public function render()
    {
        $rules = AutomationRule::query()
            ->orderBy('trigger')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('livewire.admin.automation.index', compact('rules'))->layout('layouts.admin');
    }
}

<?php

namespace App\Http\Livewire\AI;

use App\Models\AiWorkflow;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkflowBuilder extends Component
{
    public string $name = '';

    public string $status = 'inactive';

    public string $definitionJson = '{"nodes":[{"id":"new_request","type":"trigger","label":"New request"},{"id":"triage","type":"ai_task","task_type":"triage_request"},{"id":"assign","type":"human_checkpoint","label":"Admin review"}],"edges":[{"from":"new_request","to":"triage"},{"from":"triage","to":"assign"}]}';

    public function save(): void
    {
        $this->authorizeAdmin();

        $def = json_decode($this->definitionJson, true);
        if (! is_array($def)) {
            session()->flash('error', 'Definition must be valid JSON.');

            return;
        }

        AiWorkflow::create([
            'name' => $this->name ?: 'New workflow',
            'status' => $this->status,
            'definition' => $def,
            'created_by' => Auth::id(),
        ]);

        session()->flash('success', 'Workflow saved.');
        $this->name = '';
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->can('access admin panel')) {
            abort(403);
        }
    }

    public function render()
    {
        $this->authorizeAdmin();

        $workflows = AiWorkflow::query()->orderByDesc('id')->limit(50)->get();

        return view('livewire.ai.workflow-builder', [
            'workflows' => $workflows,
        ])->layout('layouts.admin', ['title' => 'AI Workflow Builder']);
    }
}

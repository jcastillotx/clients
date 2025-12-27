<?php

namespace App\Http\Livewire\Client;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectDashboard extends Component
{
    public ?int $projectId = null;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $this->projectId = Project::query()
            ->where('client_id', $user->client_id)
            ->orderBy('status')
            ->orderByDesc('id')
            ->value('id');
    }

    public function selectProject(int $id): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless(Project::query()->where('client_id', $user->client_id)->whereKey($id)->exists(), 403);
        $this->projectId = $id;
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $projects = Project::query()
            ->where('client_id', $user->client_id)
            ->orderBy('status')
            ->orderByDesc('id')
            ->get();

        $project = null;
        $milestones = collect();
        $deliverables = collect();
        $team = collect();
        $costEntries = collect();

        if ($this->projectId) {
            $project = Project::query()
                ->where('client_id', $user->client_id)
                ->with(['milestones', 'deliverables', 'teamMembers', 'costEntries'])
                ->find($this->projectId);
        }

        if ($project) {
            $milestones = $project->milestones;
            $deliverables = $project->deliverables;
            $team = $project->teamMembers;
            $costEntries = $project->costEntries->take(20);
        }

        return view('livewire.client.project-dashboard', compact('projects', 'project', 'milestones', 'deliverables', 'team', 'costEntries'));
    }
}

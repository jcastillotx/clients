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

        // Prepare data for JavaScript
        $projectData = null;
        if ($project) {
            $projectData = [
                'id' => $project->id,
                'name' => $project->name,
                'start' => optional($project->start_date)->toDateString(),
                'end' => optional($project->end_date)->toDateString(),
                'progress' => $project->calculated_progress_percent,
            ];
        }

        $milestoneData = $milestones->map(function ($m) use ($project) {
            return [
                'id' => 'm-' . $m->id,
                'name' => 'Milestone: ' . $m->name,
                'start' => ($m->due_date?->toDateString()) ?? (optional($project->start_date)->toDateString() ?? now()->toDateString()),
                'end' => ($m->due_date?->toDateString()) ?? (optional($project->start_date)->toDateString() ?? now()->toDateString()),
                'progress' => $m->completed_at ? 100 : 0,
            ];
        })->values();

        $deliverableData = $deliverables->map(function ($d) use ($project) {
            return [
                'id' => 'd-' . $d->id,
                'name' => $d->name,
                'start' => optional($d->due_date)->toDateString() ?? (optional($project->start_date)->toDateString() ?? now()->toDateString()),
                'end' => optional($d->due_date)->toDateString() ?? (optional($project->end_date)->toDateString() ?? now()->addDays(7)->toDateString()),
                'progress' => $d->completed_at ? 100 : 0,
            ];
        })->values();

        return view('livewire.client.project-dashboard', compact('projects', 'project', 'milestones', 'deliverables', 'team', 'costEntries', 'projectData', 'milestoneData', 'deliverableData'));
    }
}

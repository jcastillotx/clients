<x-app-layout>
    <x-slot name="header">Project Dashboard</x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/frappe-gantt@0.6.1/dist/frappe-gantt.css">
        <style>
            #gantt { overflow-x: auto; }
            .gantt .bar-progress { fill: #28a745; }
        </style>
    @endpush

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-folder-open mr-1"></i> Projects</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        @forelse($projects as $p)
                            <li class="nav-item">
                                <a href="#" class="nav-link {{ $project?->id === $p->id ? 'active' : '' }}" wire:click.prevent="selectProject({{ $p->id }})">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $p->name }}</span>
                                        <span class="badge badge-secondary">{{ $p->status }}</span>
                                    </div>
                                    <div class="text-muted small">Progress: {{ $p->calculated_progress_percent }}%</div>
                                </a>
                            </li>
                        @empty
                            <li class="nav-item p-3 text-muted">No projects yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            @if(!$project)
                <div class="alert alert-info">Select a project to view timeline and progress.</div>
            @else
                <div class="row">
                    <div class="col-md-4">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $project->calculated_progress_percent }}%</h3>
                                <p>Progress</p>
                            </div>
                            <div class="icon"><i class="fas fa-tasks"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>@money($project->budget_amount)</h3>
                                <p>Budget</p>
                            </div>
                            <div class="icon"><i class="fas fa-wallet"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>@money($project->actual_spend)</h3>
                                <p>Actual spend</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-stream mr-1"></i> Timeline (Gantt)</h3>
                    </div>
                    <div class="card-body">
                        <div id="gantt"></div>
                        <small class="text-muted">Touch-friendly: swipe horizontally to scroll.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-flag-checkered mr-1"></i> Milestones</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Milestone</th>
                                            <th>Due</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($milestones as $m)
                                            <tr>
                                                <td>{{ $m->name }}</td>
                                                <td class="text-muted">{{ $m->due_date?->toDateString() ?? '—' }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $m->completed_at ? 'success' : 'secondary' }}">
                                                        {{ $m->completed_at ? 'done' : 'open' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-muted text-center py-3">No milestones.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-check-square mr-1"></i> Deliverables</h3>
                            </div>
                            <div class="card-body">
                                @forelse($deliverables as $d)
                                    <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                                        <div>
                                            <div class="font-weight-bold">{{ $d->title }}</div>
                                            <div class="text-muted small">{{ $d->due_date?->toDateString() ?? '—' }}</div>
                                        </div>
                                        <span class="badge badge-{{ $d->is_done ? 'success' : 'secondary' }}">
                                            {{ $d->is_done ? 'done' : 'open' }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="text-muted">No deliverables.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-users mr-1"></i> Team</h3>
                            </div>
                            <div class="card-body">
                                @forelse($team as $u)
                                    <div class="d-flex justify-content-between border rounded p-2 mb-2">
                                        <div>
                                            <div class="font-weight-bold">{{ $u->name }}</div>
                                            <div class="text-muted small">{{ $u->pivot->role ?? 'team' }}</div>
                                        </div>
                                        <div class="text-muted small">
                                            {{ $u->pivot->hourly_rate ? '$' . number_format($u->pivot->hourly_rate, 2) . '/hr' : '' }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted">Team not assigned yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Recent cost entries</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>When</th>
                                            <th>Item</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($costEntries as $c)
                                            <tr>
                                                <td class="text-muted">{{ $c->occurred_on?->toDateString() ?? $c->created_at?->toDateString() }}</td>
                                                <td>{{ $c->description }}</td>
                                                <td class="text-right">@money($c->amount)</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-muted text-center py-3">No cost entries.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
        <script>
            document.addEventListener('livewire:navigated', () => {
                renderGantt();
            });
            document.addEventListener('DOMContentLoaded', () => {
                renderGantt();
            });
            function renderGantt() {
                const el = document.getElementById('gantt');
                if (!el) return;
                const project = @json($project ? [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start' => optional($project->start_date)->toDateString(),
                    'end' => optional($project->end_date)->toDateString(),
                    'progress' => $project->calculated_progress_percent,
                ] : null);
                if (!project) return;
                const milestones = @json($milestones->map(fn($m) => [
                    'id' => 'm-' . $m->id,
                    'name' => 'Milestone: ' . $m->name,
                    'start' => ($m->due_date?->toDateString()) ?? (optional($project->start_date)->toDateString() ?? now()->toDateString()),
                    'end' => ($m->due_date?->toDateString()) ?? (optional($project->start_date)->toDateString() ?? now()->toDateString()),
                    'progress' => $m->completed_at ? 100 : 0,
                ])->values());
                const deliverables = @json($deliverables->map(fn($d) => [
                    'id' => 'd-' . $d->id,
                    'name' => 'Deliverable: ' . $d->title,
                    'start' => optional($project->start_date)->toDateString() ?? now()->toDateString(),
                    'end' => ($d->due_date?->toDateString()) ?? (optional($project->end_date)->toDateString() ?? now()->addDays(14)->toDateString()),
                    'progress' => $d->is_done ? 100 : 0,
                ])->values());
                const tasks = [
                    {id: 'p-' + project.id, name: project.name, start: project.start || new Date().toISOString().slice(0,10), end: project.end || new Date().toISOString().slice(0,10), progress: project.progress},
                    ...milestones,
                    ...deliverables,
                ];
                el.innerHTML = '';
                try {
                    new Gantt(el, tasks, { view_mode: 'Week' });
                } catch (e) {
                    el.innerHTML = '<div class="text-muted">Unable to render timeline.</div>';
                }
            }
        </script>
    @endpush
</x-app-layout>


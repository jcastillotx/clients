
    <x-slot name="header">Project Dashboard</x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/frappe-gantt@0.6.1/dist/frappe-gantt.css">
        <style>
            #gantt { overflow-x: auto; }
            .gantt .bar-progress { fill: #28a745; }
        </style>
    @endpush

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-folder-open mr-1"></i> Projects</h3>
                </div>
                <div class="p-0">
                    <ul class="flex flex-col">
                        @forelse($projects as $p)
                            <li class="border-b border-slate-100 last:border-b-0">
                                <a href="#" class="block px-4 py-3 transition hover:bg-slate-50 {{ $project?->id === $p->id ? 'bg-slate-900 text-white hover:bg-slate-800' : 'text-slate-900' }}" wire:click.prevent="selectProject({{ $p->id }})">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">{{ $p->name }}</span>
                                        <span class="badge">{{ $p->status }}</span>
                                    </div>
                                    <div class="mt-1 text-sm {{ $project?->id === $p->id ? 'text-slate-300' : 'text-slate-500' }}">Progress: {{ $p->calculated_progress_percent }}%</div>
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-3 text-slate-500">No projects yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="lg:col-span-9">
            @if(!$project)
                <div class="alert-info">Select a project to view timeline and progress.</div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-sm">
                        <div class="relative z-10">
                            <h3 class="text-3xl font-bold">{{ $project->calculated_progress_percent }}%</h3>
                            <p class="mt-1 text-sm text-blue-100">Progress</p>
                        </div>
                        <div class="absolute bottom-4 right-4 text-6xl text-blue-400 opacity-20"><i class="fas fa-tasks"></i></div>
                    </div>
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white shadow-sm">
                        <div class="relative z-10">
                            <h3 class="text-3xl font-bold">@money($project->budget_amount)</h3>
                            <p class="mt-1 text-sm text-emerald-100">Budget</p>
                        </div>
                        <div class="absolute bottom-4 right-4 text-6xl text-emerald-400 opacity-20"><i class="fas fa-wallet"></i></div>
                    </div>
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 p-6 text-white shadow-sm">
                        <div class="relative z-10">
                            <h3 class="text-3xl font-bold">@money($project->actual_spend)</h3>
                            <p class="mt-1 text-sm text-amber-100">Actual spend</p>
                        </div>
                        <div class="absolute bottom-4 right-4 text-6xl text-amber-400 opacity-20"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-stream mr-1"></i> Timeline (Gantt)</h3>
                    </div>
                    <div class="p-4">
                        <div id="gantt"></div>
                        <small class="text-slate-500">Touch-friendly: swipe horizontally to scroll.</small>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-flag-checkered mr-1"></i> Milestones</h3>
                        </div>
                        <div class="p-0">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Milestone</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Due</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($milestones as $m)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 text-sm text-slate-900">{{ $m->name }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-500">{{ $m->due_date?->toDateString() ?? '—' }}</td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span class="badge-{{ $m->completed_at ? 'success' : 'secondary' }}">
                                                        {{ $m->completed_at ? 'done' : 'open' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="px-4 py-3 text-center text-sm text-slate-500">No milestones.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-check-square mr-1"></i> Deliverables</h3>
                        </div>
                        <div class="p-4">
                            @forelse($deliverables as $d)
                                <div class="mb-2 flex items-center justify-between rounded-lg border border-slate-200 p-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $d->title }}</div>
                                        <div class="text-sm text-slate-500">{{ $d->due_date?->toDateString() ?? '—' }}</div>
                                    </div>
                                    <span class="badge-{{ $d->is_done ? 'success' : 'secondary' }}">
                                        {{ $d->is_done ? 'done' : 'open' }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-slate-500">No deliverables.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-users mr-1"></i> Team</h3>
                        </div>
                        <div class="p-4">
                            @forelse($team as $u)
                                <div class="mb-2 flex justify-between rounded-lg border border-slate-200 p-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $u->name }}</div>
                                        <div class="text-sm text-slate-500">{{ $u->pivot->role ?? 'team' }}</div>
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        {{ $u->pivot->hourly_rate ? '$' . number_format($u->pivot->hourly_rate, 2) . '/hr' : '' }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-slate-500">Team not assigned yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-receipt mr-1"></i> Recent cost entries</h3>
                        </div>
                        <div class="p-0">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">When</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Item</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($costEntries as $c)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 text-sm text-slate-500">{{ $c->occurred_on?->toDateString() ?? $c->created_at?->toDateString() }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-900">{{ $c->description }}</td>
                                                <td class="px-4 py-3 text-right text-sm text-slate-900">@money($c->amount)</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="px-4 py-3 text-center text-sm text-slate-500">No cost entries.</td></tr>
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
                const project = @json($projectData);
                if (!project) return;
                const milestones = @json($milestoneData ?? []);
                const deliverables = @json($deliverableData ?? []);
                const defaultDate = new Date().toISOString().slice(0, 10);
                const tasks = [
                    {
                        id: 'p-' + project.id,
                        name: project.name,
                        start: project.start || defaultDate,
                        end: project.end || defaultDate,
                        progress: project.progress
                    },
                    ...(milestones || []),
                    ...(deliverables || []),
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


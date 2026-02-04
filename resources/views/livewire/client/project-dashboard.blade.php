
    <x-slot name="header">Project Dashboard</x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/frappe-gantt@0.6.1/dist/frappe-gantt.css">
        <style>
            #gantt { overflow-x: auto; }
            .gantt .bar-progress { fill: #5F5F82; }
        </style>
    @endpush>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Projects Sidebar --}}
        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="folder-open" class="h-5 w-5 text-brand-primary" />
                        <h3 class="text-base font-semibold font-heading text-brand-text">Projects</h3>
                    </div>
                </div>
                <div class="p-0">
                    <ul class="flex flex-col">
                        @forelse($projects as $p)
                            <li class="border-b border-slate-100 last:border-b-0">
                                <a href="#" class="block px-4 py-3 transition-colors {{ $project?->id === $p->id ? 'bg-brand-primary text-white hover:bg-brand-primary/90' : 'text-slate-900 hover:bg-slate-50' }}" wire:click.prevent="selectProject({{ $p->id }})">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">{{ $p->name }}</span>
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $project?->id === $p->id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $p->status }}</span>
                                    </div>
                                    <div class="mt-1 text-sm {{ $project?->id === $p->id ? 'text-slate-200' : 'text-brand-muted' }}">Progress: {{ $p->calculated_progress_percent }}%</div>
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-3 text-sm text-brand-muted">No projects yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="lg:col-span-9">
            @if(!$project)
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    Select a project to view timeline and progress.
                </div>
            @else
                {{-- KPI Cards --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                    <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-primary/10 transition-transform duration-300 group-hover:scale-110">
                            <x-icon name="clipboard-check" class="h-7 w-7 text-brand-primary" />
                        </div>
                        <h3 class="mt-4 text-3xl font-bold font-heading tracking-tight text-brand-text">{{ $project->calculated_progress_percent }}%</h3>
                        <p class="mt-1 text-sm text-brand-muted">Progress</p>
                        <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-brand-primary to-brand-secondary opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    </div>

                    <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-50 transition-transform duration-300 group-hover:scale-110">
                            <x-icon name="currency-dollar" class="h-7 w-7 text-emerald-600" />
                        </div>
                        <h3 class="mt-4 text-3xl font-bold font-heading tracking-tight text-brand-text">@money($project->budget_amount)</h3>
                        <p class="mt-1 text-sm text-brand-muted">Budget</p>
                        <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-emerald-400 to-emerald-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    </div>

                    <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-amber-50 transition-transform duration-300 group-hover:scale-110">
                            <x-icon name="chart-bar" class="h-7 w-7 text-amber-600" />
                        </div>
                        <h3 class="mt-4 text-3xl font-bold font-heading tracking-tight text-brand-text">@money($project->actual_spend)</h3>
                        <p class="mt-1 text-sm text-brand-muted">Actual spend</p>
                        <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-amber-400 to-amber-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    </div>
                </div>

                {{-- Gantt Chart --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm mb-6">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                        <div class="flex items-center gap-2">
                            <x-icon name="calendar" class="h-5 w-5 text-brand-primary" />
                            <h3 class="text-base font-semibold font-heading text-brand-text">Timeline (Gantt)</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id="gantt"></div>
                        <small class="text-brand-muted">Touch-friendly: swipe horizontally to scroll.</small>
                    </div>
                </div>

                {{-- Milestones & Deliverables --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-icon name="flag" class="h-5 w-5 text-brand-primary" />
                                <h3 class="text-base font-semibold font-heading text-brand-text">Milestones</h3>
                            </div>
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
                                            <tr class="transition-colors hover:bg-slate-50">
                                                <td class="px-4 py-3 text-sm text-brand-text">{{ $m->name }}</td>
                                                <td class="px-4 py-3 text-sm text-brand-muted">{{ $m->due_date?->toDateString() ?? '—' }}</td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $m->completed_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                                        {{ $m->completed_at ? 'done' : 'open' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="px-4 py-3 text-center text-sm text-brand-muted">No milestones.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-icon name="clipboard-check" class="h-5 w-5 text-brand-primary" />
                                <h3 class="text-base font-semibold font-heading text-brand-text">Deliverables</h3>
                            </div>
                        </div>
                        <div class="p-4">
                            @forelse($deliverables as $d)
                                <div class="mb-2 flex items-center justify-between rounded-lg border border-slate-200 p-3 transition-colors hover:bg-slate-50">
                                    <div>
                                        <div class="font-semibold text-brand-text">{{ $d->title }}</div>
                                        <div class="text-sm text-brand-muted">{{ $d->due_date?->toDateString() ?? '—' }}</div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $d->is_done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $d->is_done ? 'done' : 'open' }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-sm text-brand-muted">No deliverables.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Team & Cost Entries --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-icon name="users" class="h-5 w-5 text-brand-primary" />
                                <h3 class="text-base font-semibold font-heading text-brand-text">Team</h3>
                            </div>
                        </div>
                        <div class="p-4">
                            @forelse($team as $u)
                                <div class="mb-2 flex justify-between rounded-lg border border-slate-200 p-3 transition-colors hover:bg-slate-50">
                                    <div>
                                        <div class="font-semibold text-brand-text">{{ $u->name }}</div>
                                        <div class="text-sm text-brand-muted">{{ $u->pivot->role ?? 'team' }}</div>
                                    </div>
                                    <div class="text-sm text-brand-muted">
                                        {{ $u->pivot->hourly_rate ? '$' . number_format($u->pivot->hourly_rate, 2) . '/hr' : '' }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-brand-muted">Team not assigned yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-icon name="document-text" class="h-5 w-5 text-brand-primary" />
                                <h3 class="text-base font-semibold font-heading text-brand-text">Recent cost entries</h3>
                            </div>
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
                                            <tr class="transition-colors hover:bg-slate-50">
                                                <td class="px-4 py-3 text-sm text-brand-muted">{{ $c->occurred_on?->toDateString() ?? $c->created_at?->toDateString() }}</td>
                                                <td class="px-4 py-3 text-sm text-brand-text">{{ $c->description }}</td>
                                                <td class="px-4 py-3 text-right text-sm text-brand-text">@money($c->amount)</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="px-4 py-3 text-center text-sm text-brand-muted">No cost entries.</td></tr>
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
                    el.innerHTML = '<div class="text-brand-muted">Unable to render timeline.</div>';
                }
            }
        </script>
    @endpush

<!-- Gantt Chart View -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="ganttChart(@js($tasks->filter(fn($t) => $t->start_date || $t->due_date)->map(fn($t) => [
    'id' => $t->id,
    'name' => $t->title,
    'start' => $t->start_date?->format('Y-m-d') ?? $t->due_date?->format('Y-m-d'),
    'end' => $t->due_date?->format('Y-m-d') ?? $t->start_date?->format('Y-m-d'),
    'progress' => $t->progress,
    'priority' => $t->priority,
    'assignees' => $t->assignees->pluck('name')->join(', '),
    'column' => $t->column->name,
])->values()))">
    <div class="border-b border-slate-200 p-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Gantt Chart</h3>
            <div class="flex items-center gap-2">
                <button @click="zoomOut()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    <i class="fas fa-search-minus"></i>
                </button>
                <button @click="zoomIn()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button @click="scrollToToday()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Today
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto" id="gantt-container">
        @if($tasks->filter(fn($t) => $t->start_date || $t->due_date)->count() > 0)
            <div class="min-w-[1200px]">
                <!-- Timeline Header -->
                <div class="flex border-b border-slate-200 bg-slate-50">
                    <div class="w-80 flex-shrink-0 border-r border-slate-200 p-3">
                        <span class="text-xs font-semibold uppercase text-slate-600">Task</span>
                    </div>
                    <div class="flex-1 overflow-hidden" x-ref="timelineHeader">
                        <div class="flex" x-ref="dates">
                            <!-- Dates will be rendered by JS -->
                        </div>
                    </div>
                </div>

                <!-- Tasks -->
                <div class="divide-y divide-slate-100">
                    @foreach($tasks->filter(fn($t) => $t->start_date || $t->due_date) as $task)
                        <div class="flex hover:bg-slate-50">
                            <!-- Task Info -->
                            <div class="w-80 flex-shrink-0 border-r border-slate-200 p-3">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-{{ $task->priority_color }}-500"></span>
                                    <span class="font-medium text-slate-900 truncate">{{ Str::limit($task->title, 30) }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                    <span>{{ $task->column->name }}</span>
                                    @if($task->assignees->count() > 0)
                                        <span>{{ $task->assignees->first()->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <!-- Gantt Bar -->
                            <div class="relative flex-1 p-2" style="min-height: 50px;">
                                <div
                                    class="gantt-bar absolute top-1/2 -translate-y-1/2 rounded-lg cursor-pointer transition hover:opacity-80"
                                    style="background-color: {{ $task->column->color }}; height: 24px;"
                                    data-task-id="{{ $task->id }}"
                                    data-start="{{ ($task->start_date ?? $task->due_date)->format('Y-m-d') }}"
                                    data-end="{{ ($task->due_date ?? $task->start_date)->format('Y-m-d') }}"
                                    wire:click="openTaskModal({{ $task->id }})"
                                    title="{{ $task->title }}"
                                >
                                    @if($task->progress > 0)
                                        <div class="absolute left-0 top-0 h-full rounded-l-lg bg-black/20" style="width: {{ $task->progress }}%"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="p-12 text-center">
                <div class="mb-4 text-4xl text-slate-300">
                    <i class="fas fa-chart-gantt"></i>
                </div>
                <p class="text-sm text-slate-500">No tasks with dates to display. Add start/due dates to tasks to see them on the Gantt chart.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function ganttChart(tasks) {
    return {
        tasks: tasks,
        dayWidth: 40,
        startDate: null,
        endDate: null,
        today: new Date(),

        init() {
            if (this.tasks.length === 0) return;

            // Calculate date range
            const dates = this.tasks.flatMap(t => [new Date(t.start), new Date(t.end)]);
            this.startDate = new Date(Math.min(...dates));
            this.endDate = new Date(Math.max(...dates));

            // Add padding
            this.startDate.setDate(this.startDate.getDate() - 7);
            this.endDate.setDate(this.endDate.getDate() + 14);

            this.renderTimeline();
            this.positionBars();
            this.scrollToToday();
        },

        renderTimeline() {
            const header = this.$refs.dates;
            if (!header) return;

            header.innerHTML = '';
            let current = new Date(this.startDate);

            while (current <= this.endDate) {
                const day = document.createElement('div');
                day.className = 'flex-shrink-0 text-center border-r border-slate-100 py-2';
                day.style.width = this.dayWidth + 'px';

                const isToday = current.toDateString() === this.today.toDateString();
                const isWeekend = current.getDay() === 0 || current.getDay() === 6;

                day.innerHTML = `
                    <div class="text-[10px] ${isToday ? 'font-bold text-blue-600' : 'text-slate-400'}">${current.toLocaleDateString('en', { weekday: 'short' })}</div>
                    <div class="text-xs ${isToday ? 'font-bold text-blue-600' : (isWeekend ? 'text-slate-400' : 'text-slate-600')}">${current.getDate()}</div>
                `;

                if (isToday) {
                    day.classList.add('bg-blue-50');
                } else if (isWeekend) {
                    day.classList.add('bg-slate-50');
                }

                header.appendChild(day);
                current.setDate(current.getDate() + 1);
            }
        },

        positionBars() {
            document.querySelectorAll('.gantt-bar').forEach(bar => {
                const start = new Date(bar.dataset.start);
                const end = new Date(bar.dataset.end);

                const startOffset = Math.floor((start - this.startDate) / (1000 * 60 * 60 * 24));
                const duration = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1);

                bar.style.left = (startOffset * this.dayWidth) + 'px';
                bar.style.width = (duration * this.dayWidth - 4) + 'px';
            });
        },

        zoomIn() {
            this.dayWidth = Math.min(80, this.dayWidth + 10);
            this.renderTimeline();
            this.positionBars();
        },

        zoomOut() {
            this.dayWidth = Math.max(20, this.dayWidth - 10);
            this.renderTimeline();
            this.positionBars();
        },

        scrollToToday() {
            const container = document.getElementById('gantt-container');
            if (!container || !this.startDate) return;

            const todayOffset = Math.floor((this.today - this.startDate) / (1000 * 60 * 60 * 24));
            container.scrollLeft = Math.max(0, (todayOffset * this.dayWidth) - 200);
        }
    };
}
</script>
@endpush

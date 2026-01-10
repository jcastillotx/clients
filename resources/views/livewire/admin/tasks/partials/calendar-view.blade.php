<!-- Calendar View -->
<div class="rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="calendarView()">
    <!-- Calendar Header -->
    <div class="flex items-center justify-between border-b border-slate-200 p-4">
        <div class="flex items-center gap-4">
            <button @click="prevMonth()" class="rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50">
                <i class="fas fa-chevron-left"></i>
            </button>
            <h3 class="text-lg font-semibold text-slate-900" x-text="monthYear"></h3>
            <button @click="nextMonth()" class="rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <button @click="goToToday()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Today
        </button>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <div class="border-r border-slate-200 p-2 text-center text-xs font-semibold uppercase text-slate-600 last:border-r-0">
                {{ $day }}
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-7">
        <template x-for="(week, weekIndex) in weeks" :key="weekIndex">
            <template x-for="(day, dayIndex) in week" :key="dayIndex">
                <div
                    class="min-h-[120px] border-b border-r border-slate-200 p-1"
                    :class="{
                        'bg-slate-50': !day.isCurrentMonth,
                        'bg-blue-50': day.isToday
                    }"
                >
                    <div class="mb-1 text-right">
                        <span
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs"
                            :class="{
                                'bg-blue-600 text-white font-semibold': day.isToday,
                                'text-slate-400': !day.isCurrentMonth,
                                'text-slate-900': day.isCurrentMonth && !day.isToday
                            }"
                            x-text="day.date"
                        ></span>
                    </div>
                    <div class="space-y-1">
                        <template x-for="task in getTasksForDay(day.fullDate)" :key="task.id">
                            <div
                                class="cursor-pointer truncate rounded px-1.5 py-0.5 text-xs text-white"
                                :style="{ backgroundColor: task.color }"
                                :title="task.title"
                                @click="$wire.openTaskModal(task.id)"
                            >
                                <span x-text="task.title"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </template>
    </div>
</div>

@push('scripts')
<script>
function calendarView() {
    return {
        currentDate: new Date(),
        tasks: @js($tasks->map(fn($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'due_date' => $t->due_date?->format('Y-m-d'),
            'start_date' => $t->start_date?->format('Y-m-d'),
            'color' => $t->column->color,
            'priority' => $t->priority,
        ])),

        get monthYear() {
            return this.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },

        get weeks() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);

            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - startDate.getDay());

            const weeks = [];
            let current = new Date(startDate);

            while (current <= lastDay || current.getDay() !== 0) {
                if (current.getDay() === 0) {
                    weeks.push([]);
                }

                weeks[weeks.length - 1].push({
                    date: current.getDate(),
                    fullDate: current.toISOString().split('T')[0],
                    isCurrentMonth: current.getMonth() === month,
                    isToday: current.toDateString() === new Date().toDateString()
                });

                current.setDate(current.getDate() + 1);

                if (weeks.length > 6) break;
            }

            // Ensure we have 6 weeks
            while (weeks.length < 6) {
                weeks.push([]);
                for (let i = 0; i < 7; i++) {
                    weeks[weeks.length - 1].push({
                        date: current.getDate(),
                        fullDate: current.toISOString().split('T')[0],
                        isCurrentMonth: false,
                        isToday: false
                    });
                    current.setDate(current.getDate() + 1);
                }
            }

            return weeks;
        },

        getTasksForDay(dateStr) {
            return this.tasks.filter(t => t.due_date === dateStr || t.start_date === dateStr);
        },

        prevMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
        },

        nextMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
        },

        goToToday() {
            this.currentDate = new Date();
        }
    };
}
</script>
@endpush
